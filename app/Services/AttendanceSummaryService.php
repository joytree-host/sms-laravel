<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\Term;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * All attendance calculation logic lives here — never in controllers.
 *
 * Percentage rule (documented once, deliberately, per the Phase 4 spec's
 * instruction not to scatter an implicit policy around the codebase):
 *
 *   Attendance % = Present days / Total recorded days × 100
 *
 * Only a literal "present" day counts toward the numerator. Late and
 * Excused both count toward the denominator (they were recorded,
 * applicable days a decision was made about) but NOT toward the
 * numerator — same treatment as Absent for this calculation. This is the
 * simplest, most literal reading of the spec's own formula. If a school
 * wants Late or Excused to count differently, that's a real policy
 * decision — this method is the one place that would ever need to change
 * to support a configurable rule later.
 *
 * "Total recorded days" means exactly what it says: the count of
 * attendance rows that actually exist for the student in scope. This
 * app has no school-calendar/holiday system (deliberately out of scope
 * for Phase 4 — see PHASE4.md), so a percentage here reflects only days
 * attendance was actually taken, never assumed calendar days, weekends,
 * or holidays.
 */
class AttendanceSummaryService
{
    /**
     * @return array{present:int,absent:int,late:int,excused:int,total:int,percentage:?float}
     */
    public function summaryFor(Student $student, ?Term $term = null, ?AcademicYear $academicYear = null): array
    {
        $query = Attendance::where('student_id', $student->id)
            ->when($term, fn ($q) => $q->where('term_id', $term->id))
            ->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id));

        $counts = (clone $query)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $present = (int) ($counts['present'] ?? 0);
        $absent = (int) ($counts['absent'] ?? 0);
        $late = (int) ($counts['late'] ?? 0);
        $excused = (int) ($counts['excused'] ?? 0);
        $total = $present + $absent + $late + $excused;

        $percentage = $total > 0 ? round(($present / $total) * 100, 2) : null;

        return [
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'excused' => $excused,
            'total' => $total,
            'percentage' => $percentage,
        ];
    }

    /** Paginated attendance history for one student, newest first. */
    public function historyFor(Student $student, ?Term $term = null, ?AcademicYear $academicYear = null, int $perPage = 30): LengthAwarePaginator
    {
        return Attendance::where('student_id', $student->id)
            ->when($term, fn ($q) => $q->where('term_id', $term->id))
            ->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id))
            ->with(['term', 'academicYear'])
            ->orderByDesc('attendance_date')
            ->paginate($perPage);
    }
}
