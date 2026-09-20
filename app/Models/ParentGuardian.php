<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Named ParentGuardian (table: `parents`) since `Parent` collides with a
 * reserved word in some PHP contexts and is easy to confuse with the
 * Eloquent-internal notion of a "parent" model in relations.
 */
class ParentGuardian extends Model
{
    use HasFactory;

    protected $table = 'parents';

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'phone',
        'address',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'parent_student', 'parent_id', 'student_id')
            ->withPivot(['relationship', 'is_primary'])
            ->withTimestamps();
    }

    /**
     * Attach/update a single student link, enforcing "at most one primary
     * guardian per student" in the application layer (MySQL has no partial
     * unique index to do this at the DB level — see the parent_student
     * migration).
     */
    public function syncStudent(Student $student, ?string $relationship, bool $isPrimary): void
    {
        if ($isPrimary) {
            \Illuminate\Support\Facades\DB::table('parent_student')
                ->where('student_id', $student->id)
                ->update(['is_primary' => false]);
        }

        $this->students()->syncWithoutDetaching([
            $student->id => ['relationship' => $relationship, 'is_primary' => $isPrimary],
        ]);
    }

    /** Student IDs this guardian is authorized to view — the backbone of parent-side authorization. */
    public function studentIds(): array
    {
        return $this->students()->pluck('students.id')->all();
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
