<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A published/draft summary for one student's one term. Per-subject
 * breakdown is NOT stored here — see ResultCalculationService, which
 * computes it live from Assessment/Score every time it's needed, so there
 * is exactly one source of truth for subject-level numbers.
 */
class TermResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'class_id', 'academic_year_id', 'term_id', 'status',
        'total_subjects', 'average_percentage', 'position', 'published_at', 'published_by',
    ];

    protected function casts(): array
    {
        return [
            'average_percentage' => 'decimal:2',
            'published_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }
}
