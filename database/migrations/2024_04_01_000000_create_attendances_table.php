<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per student per calendar day. `status` is a fixed DB
        // enum, not an admin-configurable lookup table (unlike
        // assessment_types) — the Phase 4 spec explicitly says "do not
        // allow arbitrary status values"; adding a 5th status later is a
        // deliberate migration + a one-line whitelist change in the
        // Attendance model, not a data-entry screen.
        //
        // class_id/academic_year_id/term_id are captured at the time
        // attendance is recorded (same pattern as enrollments.class_id and
        // scores.subject_id) — a student's later class change never
        // rewrites their attendance history.
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('school_classes')->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->foreignId('term_id')->constrained('terms')->restrictOnDelete();
            $table->date('attendance_date');
            $table->enum('status', ['present', 'absent', 'late', 'excused'])->index();
            $table->string('note', 255)->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['student_id', 'attendance_date'], 'attendance_per_student_per_day');
            $table->index(['class_id', 'attendance_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
