<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A configurable row of one Academic Year's grading scale (e.g. 80-100 =
 * A = Excellent for 2025/2026). Deliberately no grade-point value anywhere
 * on this model — grade + remark only. Scoped per year so a school can
 * change its scale in a future year without altering how past years'
 * results are graded — see ResultCalculationService::gradeBandFor().
 */
class GradeBand extends Model
{
    use HasFactory;

    protected $fillable = ['academic_year_id', 'min_score', 'max_score', 'grade', 'remark', 'status'];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
