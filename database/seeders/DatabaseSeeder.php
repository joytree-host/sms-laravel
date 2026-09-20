<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\ParentGuardian;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Admin account
        User::create([
            'name' => 'School Administrator',
            'email' => 'admin@futurelegacyschool.edu',
            'password' => 'ChangeMe123!', // hashed automatically via the User model's 'hashed' cast
            'role' => 'admin',
            'status' => 'active',
        ]);

        // 2. Academic structure
        $year = AcademicYear::create([
            'name' => '2025/2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-07-31',
            'is_current' => true,
        ]);

        $classA = SchoolClass::create(['name' => 'Grade 9A', 'academic_year_id' => $year->id]);
        $classB = SchoolClass::create(['name' => 'Grade 9B', 'academic_year_id' => $year->id]);

        // 2b. Subjects (Phase 2) + class-subject assignment
        $mathematics = Subject::create(['code' => 'MATH101', 'name' => 'Mathematics']);
        $english = Subject::create(['code' => 'ENG101', 'name' => 'English Language']);
        $science = Subject::create(['code' => 'SCI101', 'name' => 'Integrated Science']);

        foreach ([$classA, $classB] as $class) {
            $class->subjects()->attach(
                [$mathematics->id, $english->id, $science->id],
                ['academic_year_id' => $year->id]
            );
        }

        // 3. Sample students (fictional data only, per README instructions)
        $studentsData = [
            ['first' => 'Ama', 'last' => 'Owusu', 'class' => $classA],
            ['first' => 'Kwesi', 'last' => 'Mensah', 'class' => $classA],
            ['first' => 'Efua', 'last' => 'Boateng', 'class' => $classB],
        ];

        $students = [];
        foreach ($studentsData as $i => $data) {
            $studentUser = User::create([
                'name' => "{$data['first']} {$data['last']}",
                'email' => strtolower("{$data['first']}.{$data['last']}@students.futurelegacyschool.edu"),
                'password' => 'ChangeMe123!',
                'role' => 'student',
                'status' => 'active',
            ]);

            $student = Student::create([
                'user_id' => $studentUser->id,
                'admission_number' => 'FLS-2025-' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'first_name' => $data['first'],
                'last_name' => $data['last'],
                'date_of_birth' => '2011-04-12',
                'gender' => $i % 2 === 0 ? 'female' : 'male',
                'class_id' => $data['class']->id,
                'academic_year_id' => $year->id,
                'admission_date' => '2025-09-01',
                'status' => 'active',
            ]);

            // 3b. Enroll every seeded student in their class's subjects (Phase 2)
            foreach ($data['class']->subjects as $subject) {
                $student->enrollments()->create([
                    'subject_id' => $subject->id,
                    'class_id' => $data['class']->id,
                    'academic_year_id' => $year->id,
                ]);
            }

            $students[] = $student;
        }

        // 4. Sample parents, each linked to one or more students.
        // Yaw Owusu demonstrates the "multiple children per parent" case —
        // linked to both Ama and Efua, marked primary guardian for each.
        $parentUser1 = User::create([
            'name' => 'Yaw Owusu',
            'email' => 'yaw.owusu@example.com',
            'password' => 'ChangeMe123!',
            'role' => 'parent',
            'status' => 'active',
        ]);
        $parent1 = ParentGuardian::create([
            'user_id' => $parentUser1->id,
            'first_name' => 'Yaw',
            'last_name' => 'Owusu',
        ]);
        $parent1->syncStudent($students[0], 'Father', true);
        $parent1->syncStudent($students[2], 'Father', true);

        $parentUser2 = User::create([
            'name' => 'Abena Mensah',
            'email' => 'abena.mensah@example.com',
            'password' => 'ChangeMe123!',
            'role' => 'parent',
            'status' => 'active',
        ]);
        $parent2 = ParentGuardian::create([
            'user_id' => $parentUser2->id,
            'first_name' => 'Abena',
            'last_name' => 'Mensah',
        ]);
        $parent2->syncStudent($students[1], 'Mother', true);

        // 5. Terms (Phase 3)
        $firstTerm = \App\Models\Term::create(['academic_year_id' => $year->id, 'name' => 'First Term', 'sequence' => 1, 'is_current' => true]);
        \App\Models\Term::create(['academic_year_id' => $year->id, 'name' => 'Second Term', 'sequence' => 2]);
        \App\Models\Term::create(['academic_year_id' => $year->id, 'name' => 'Third Term', 'sequence' => 3]);

        // 5b. Assessment types — configurable, not hard-coded (Basic School, no GPA)
        $continuousAssessment = \App\Models\AssessmentType::create(['name' => 'Continuous Assessment']);
        $examination = \App\Models\AssessmentType::create(['name' => 'Examination']);

        // 5c. Grading scale — configurable per academic year, admin can
        // add/edit/remove bands. A future year's scale can differ without
        // touching this one.
        $bands = [
            ['min_score' => 80, 'max_score' => 100, 'grade' => 'A', 'remark' => 'Excellent'],
            ['min_score' => 70, 'max_score' => 79, 'grade' => 'B', 'remark' => 'Very Good'],
            ['min_score' => 60, 'max_score' => 69, 'grade' => 'C', 'remark' => 'Good'],
            ['min_score' => 50, 'max_score' => 59, 'grade' => 'D', 'remark' => 'Pass'],
            ['min_score' => 0, 'max_score' => 49, 'grade' => 'F', 'remark' => 'Needs Improvement'],
        ];
        foreach ($bands as $band) {
            \App\Models\GradeBand::create($band + ['academic_year_id' => $year->id]);
        }

        // 5d. Sample assessments for Mathematics, Grade 9A, First Term —
        // demonstrates the school's own CA=40 + Exam=60 split via each
        // assessment's own max_score, not a hard-coded formula.
        $caAssessment = \App\Models\Assessment::create([
            'name' => 'First Term CA',
            'assessment_type_id' => $continuousAssessment->id,
            'academic_year_id' => $year->id,
            'term_id' => $firstTerm->id,
            'class_id' => $classA->id,
            'subject_id' => $mathematics->id,
            'max_score' => 40,
        ]);
        $examAssessment = \App\Models\Assessment::create([
            'name' => 'First Term Examination',
            'assessment_type_id' => $examination->id,
            'academic_year_id' => $year->id,
            'term_id' => $firstTerm->id,
            'class_id' => $classA->id,
            'subject_id' => $mathematics->id,
            'max_score' => 60,
        ]);

        // 5e. Sample scores for the two Grade 9A students, via each one's
        // actual Mathematics enrollment (never a bare student/subject pair).
        foreach ([$students[0], $students[1]] as $index => $student) {
            $enrollment = $student->enrollments()->where('subject_id', $mathematics->id)->first();
            if (! $enrollment) {
                continue;
            }

            $caScore = $index === 0 ? 35 : 28;
            $examScore = $index === 0 ? 50 : 40;

            \App\Models\Score::create([
                'assessment_id' => $caAssessment->id,
                'enrollment_id' => $enrollment->id,
                'student_id' => $student->id,
                'subject_id' => $mathematics->id,
                'class_id' => $classA->id,
                'academic_year_id' => $year->id,
                'term_id' => $firstTerm->id,
                'score' => $caScore,
            ]);
            \App\Models\Score::create([
                'assessment_id' => $examAssessment->id,
                'enrollment_id' => $enrollment->id,
                'student_id' => $student->id,
                'subject_id' => $mathematics->id,
                'class_id' => $classA->id,
                'academic_year_id' => $year->id,
                'term_id' => $firstTerm->id,
                'score' => $examScore,
            ]);
        }

        // 5f. Compute (but leave as draft — publishing is a deliberate
        // admin action, not something a seeder should do) term results for
        // Grade 9A's First Term.
        \App\Models\Setting::setRankingEnabled(true); // explicit, matches the default
        app(\App\Services\ResultCalculationService::class)->computeForClassTerm($classA, $firstTerm);

        // 6. Sample attendance (Phase 4) — a few recorded days for Grade
        // 9A's students, deliberately not every calendar day, to
        // demonstrate that the summary reflects only days actually taken.
        foreach ([$students[0], $students[1]] as $index => $student) {
            $days = [
                ['2025-09-08', 'present'],
                ['2025-09-09', 'present'],
                ['2025-09-10', $index === 0 ? 'present' : 'absent'],
                ['2025-09-11', 'late'],
                ['2025-09-12', $index === 0 ? 'present' : 'excused'],
            ];
            foreach ($days as [$date, $status]) {
                \App\Models\Attendance::create([
                    'student_id' => $student->id,
                    'class_id' => $classA->id,
                    'academic_year_id' => $year->id,
                    'term_id' => $firstTerm->id,
                    'attendance_date' => $date,
                    'status' => $status,
                ]);
            }
        }
    }
}
