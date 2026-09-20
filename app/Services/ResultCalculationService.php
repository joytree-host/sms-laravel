<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\GradeBand;
use App\Models\SchoolClass;
use App\Models\Score;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Models\TermResult;
use Illuminate\Support\Collection;

/**
 * All result-calculation logic for the Basic School lives here — never in
 * controllers. Deliberately named for what it actually does; there is no
 * GPA/CGPA concept anywhere in this class or its output.
 *
 * Core rule: a subject's total is never a hard-coded split (e.g. "CA=40,
 * Exam=60"). It's the sum of the student's scores across whatever
 * assessments the admin actually defined for that subject/class/term/year,
 * against the sum of those assessments' own max_score values. A school
 * that wants CA=40+Exam=60=100 gets that by creating assessments with
 * those max scores — nothing here assumes that specific split.
 */
class ResultCalculationService
{
    /**
     * One subject's result for one student in one term. Missing scores
     * count as 0 for any assessment that was actually held — the normal
     * school convention for a missed test/assignment.
     */
    public function subjectResult(Student $student, Subject $subject, SchoolClass $class, Term $term): array
    {
        $assessments = Assessment::query()
            ->where('class_id', $class->id)
            ->where('subject_id', $subject->id)
            ->where('term_id', $term->id)
            ->where('academic_year_id', $class->academic_year_id)
            ->where('status', 'active')
            ->orderBy('assessment_date')
            ->get();

        $totalMaxScore = (int) $assessments->sum('max_score');

        $scoresByAssessment = Score::query()
            ->where('student_id', $student->id)
            ->whereIn('assessment_id', $assessments->pluck('id'))
            ->get()
            ->keyBy('assessment_id');

        $breakdown = [];
        $totalScore = 0.0;

        foreach ($assessments as $assessment) {
            $score = $scoresByAssessment->get($assessment->id);
            $obtained = $score ? (float) $score->score : 0.0;
            $totalScore += $obtained;

            $breakdown[] = [
                'assessment' => $assessment,
                'score' => $score?->score,
                'recorded' => $score !== null,
            ];
        }

        $percentage = $totalMaxScore > 0 ? round(($totalScore / $totalMaxScore) * 100, 2) : null;
        // Always the class's OWN academic year — a historical result keeps
        // using the grading scale that was actually in force for its year,
        // even if a future year's scale is later changed. See
        // gradeBandFor() and the grade_bands migration for why.
        $band = $percentage !== null ? $this->gradeBandFor($percentage, $class->academic_year_id) : null;

        return [
            'subject' => $subject,
            'assessments' => $breakdown,
            'total_score' => round($totalScore, 2),
            'total_max_score' => $totalMaxScore,
            'percentage' => $percentage,
            'grade' => $band?->grade,
            'remark' => $band?->remark,
        ];
    }

    /** Finds the configured grade band a percentage falls into for a specific academic year, or null if that year's admin hasn't covered that range. */
    public function gradeBandFor(float $percentage, int $academicYearId): ?GradeBand
    {
        return GradeBand::query()
            ->where('academic_year_id', $academicYearId)
            ->where('status', 'active')
            ->where('min_score', '<=', $percentage)
            ->where('max_score', '>=', $percentage)
            ->orderByDesc('min_score')
            ->first();
    }

    /**
     * The full set of subject results for one student in one term, i.e.
     * everything a result page needs, built from the student's active
     * enrollments in that class/year — not from a hard-coded subject list.
     */
    public function studentTermBreakdown(Student $student, SchoolClass $class, Term $term): Collection
    {
        $subjectIds = $student->enrollments()
            ->where('class_id', $class->id)
            ->where('academic_year_id', $class->academic_year_id)
            ->where('status', 'enrolled')
            ->pluck('subject_id');

        return Subject::whereIn('id', $subjectIds)
            ->orderBy('name')
            ->get()
            ->map(fn (Subject $subject) => $this->subjectResult($student, $subject, $class, $term));
    }

    /**
     * Computes (and upserts) TermResult rows for every active student in a
     * class, for one term. Position/ranking is computed only when the
     * admin has enabled it (Setting::rankingEnabled()) — when disabled, no
     * ranking work is done at all and any previously-stored position is
     * cleared, so a disabled setting can never leave a stale rank sitting
     * around to be displayed by mistake. Never changes an existing
     * result's publish status; only its numbers. A school correcting a
     * score after publishing should see the correction reflected, not a
     * silently-unpublished result.
     */
    public function computeForClassTerm(SchoolClass $class, Term $term): Collection
    {
        $students = $class->students()->where('status', 'active')->get();

        $computed = $students->map(function (Student $student) use ($class, $term) {
            $breakdown = $this->studentTermBreakdown($student, $class, $term);
            $percentages = $breakdown->pluck('percentage')->filter(fn ($p) => $p !== null);

            $average = $percentages->isNotEmpty() ? round($percentages->avg(), 2) : null;

            return TermResult::updateOrCreate(
                ['student_id' => $student->id, 'term_id' => $term->id],
                [
                    'class_id' => $class->id,
                    'academic_year_id' => $class->academic_year_id,
                    'total_subjects' => $breakdown->count(),
                    'average_percentage' => $average,
                ]
            );
        });

        if (Setting::rankingEnabled()) {
            $this->assignPositions($computed);
        } else {
            $computed->each(fn (TermResult $r) => $r->update(['position' => null]));
        }

        return $computed;
    }

    /** Standard "competition ranking" (1, 1, 3, 4...) — ties share a position. */
    private function assignPositions(Collection $termResults): void
    {
        $ranked = $termResults
            ->filter(fn (TermResult $r) => $r->average_percentage !== null)
            ->sortByDesc('average_percentage')
            ->values();

        $heldRank = null;
        $previousAverage = null;

        foreach ($ranked as $index => $termResult) {
            $naturalRank = $index + 1;
            // average_percentage is a decimal(5,2) cast to a fixed-precision
            // string ("85.50"), so a plain numeric comparison is exact here
            // — no bcmath dependency needed for a 2-decimal-place value.
            $isTiedWithPrevious = $previousAverage !== null
                && (float) $termResult->average_percentage === (float) $previousAverage;

            $rank = $isTiedWithPrevious ? $heldRank : $naturalRank;

            $termResult->update(['position' => $rank]);

            $heldRank = $rank;
            $previousAverage = $termResult->average_percentage;
        }

        // Students with no computable average (no scores recorded at all) have no position.
        $termResults->filter(fn (TermResult $r) => $r->average_percentage === null)
            ->each(fn (TermResult $r) => $r->update(['position' => null]));
    }
}
