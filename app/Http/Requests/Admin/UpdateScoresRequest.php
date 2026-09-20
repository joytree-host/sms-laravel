<?php

namespace App\Http\Requests\Admin;

use App\Models\Enrollment;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a full page of score entries for one Assessment in a single
 * submission. Every rule here is server-side and re-derives its own
 * ground truth (the assessment's own max_score, and a live enrollment
 * check) rather than trusting anything about the row beyond the raw
 * student_id/score pair — a forged student_id or an inflated score cannot
 * get through no matter what the browser sent.
 */
class UpdateScoresRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageScores', $this->route('assessment'));
    }

    public function rules(): array
    {
        $assessment = $this->route('assessment');

        return [
            'scores' => ['array'],
            'scores.*.student_id' => ['required', 'distinct', 'exists:students,id'],
            'scores.*.score' => [
                'nullable', // blank = "not entered yet", not a score of 0
                'numeric',
                'min:0',
                'max:' . $assessment->max_score,
                function ($attribute, $value, $fail) use ($assessment) {
                    // $attribute is e.g. "scores.2.score" — pull the sibling student_id for this same row.
                    $index = explode('.', $attribute)[1] ?? null;
                    $studentId = $index !== null ? $this->input("scores.{$index}.student_id") : null;

                    if (! $studentId) {
                        return; // the student_id rule above will already report this row as invalid
                    }

                    $isEnrolled = Enrollment::where('student_id', $studentId)
                        ->where('subject_id', $assessment->subject_id)
                        ->where('class_id', $assessment->class_id)
                        ->where('academic_year_id', $assessment->academic_year_id)
                        ->where('status', 'enrolled')
                        ->exists();

                    if (! $isEnrolled) {
                        $fail('This student is not enrolled in this subject for this class/year — the score was rejected.');
                    }
                },
            ],
        ];
    }
}
