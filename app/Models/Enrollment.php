<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A student's enrollment in one subject for one academic year. Deliberately
 * thin in Phase 2 — Phase 3 (assessments/scores) hangs off this record
 * rather than off the student directly, so "which subjects is this student
 * actually being graded in this year" has one unambiguous source.
 */
class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = ['student_id', 'subject_id', 'class_id', 'academic_year_id', 'status'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(Score::class);
    }
}
