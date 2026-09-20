<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One student's attendance for one calendar day. `status` is restricted
 * to STATUSES below — never trust a raw string from a request; validate
 * against this list (see StoreAttendanceBulkRequest).
 */
class Attendance extends Model
{
    use HasFactory;

    public const STATUSES = ['present', 'absent', 'late', 'excused'];

    protected $fillable = [
        'student_id', 'class_id', 'academic_year_id', 'term_id',
        'attendance_date', 'status', 'note', 'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
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

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
