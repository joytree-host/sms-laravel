<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\EnrollmentController;
use App\Http\Controllers\Admin\ParentController as AdminParentController;
use App\Http\Controllers\Admin\SchoolClassController;
use App\Http\Controllers\Admin\StudentController as AdminStudentController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\AssessmentController;
use App\Http\Controllers\Admin\AssessmentTypeController;
use App\Http\Controllers\Admin\GradeBandController;
use App\Http\Controllers\Admin\ScoreController;
use App\Http\Controllers\Admin\TermController;
use App\Http\Controllers\Admin\TermResultController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Parent\ChildController;
use App\Http\Controllers\Parent\DashboardController as ParentDashboard;
use App\Http\Controllers\Parent\ResultController as ParentResultController;
use App\Http\Controllers\Parent\AttendanceController as ParentAttendanceController;
use App\Http\Controllers\Student\DashboardController as StudentDashboard;
use App\Http\Controllers\Student\ProfileController as StudentProfileController;
use App\Http\Controllers\Student\ResultController as StudentResultController;
use App\Http\Controllers\Student\AttendanceController as StudentAttendanceController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Alias of Laravel's own default health route (bootstrap/app.php's
// `health: '/up'`) — added because JoyTree's deployment spec asked for
// this exact literal path. '/up' remains the primary one Laravel itself
// wires up.
Route::get('/health', fn () => response('OK', 200));

Route::get('/', function () {
    if (! Auth::check()) {
        return redirect('/login');
    }

    return match (Auth::user()->role) {
        'admin' => redirect('/admin/dashboard'),
        'student' => redirect('/student/dashboard'),
        'parent' => redirect('/parent/dashboard'),
        default => redirect('/login'),
    };
});

// --- Authentication -------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])
        ->middleware('throttle:login'); // 5 attempts/minute, keyed by email+IP — see AppServiceProvider
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// --- Admin ------------------------------------------------------------
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [AdminDashboard::class, 'index'])->name('dashboard');

        // Student & Parent Management (Phase 2) ---------------------------
        Route::resource('students', AdminStudentController::class);
        Route::patch('students/{student}/toggle-account', [AdminStudentController::class, 'toggleAccountStatus'])
            ->name('students.toggle-account');
        Route::post('students/{student}/enrollments', [EnrollmentController::class, 'store'])
            ->name('students.enrollments.store');
        Route::delete('students/{student}/enrollments/{enrollment}', [EnrollmentController::class, 'destroy'])
            ->name('students.enrollments.destroy');

        Route::resource('parents', AdminParentController::class);
        Route::patch('parents/{parent}/toggle-account', [AdminParentController::class, 'toggleAccountStatus'])
            ->name('parents.toggle-account');

        // Academic Structure (Phase 2) -------------------------------------
        Route::resource('classes', SchoolClassController::class)->parameters(['classes' => 'class']);
        Route::get('classes/{class}/subjects', [SchoolClassController::class, 'editSubjects'])
            ->name('classes.subjects.edit');
        Route::put('classes/{class}/subjects', [SchoolClassController::class, 'updateSubjects'])
            ->name('classes.subjects.update');

        Route::resource('subjects', SubjectController::class);

        // Assessment & Results Management (Phase 3) -------------------------
        Route::resource('terms', TermController::class)->except(['show']);
        Route::resource('assessment-types', AssessmentTypeController::class)->except(['show']);
        Route::resource('grade-bands', GradeBandController::class)->except(['show']);

        Route::resource('assessments', AssessmentController::class);
        Route::get('assessments/{assessment}/scores', [ScoreController::class, 'edit'])->name('assessments.scores.edit');
        Route::put('assessments/{assessment}/scores', [ScoreController::class, 'update'])->name('assessments.scores.update');

        Route::get('term-results', [TermResultController::class, 'index'])->name('term-results.index');
        Route::post('term-results/compute', [TermResultController::class, 'compute'])->name('term-results.compute');
        Route::get('term-results/{termResult}', [TermResultController::class, 'show'])->name('term-results.show');
        Route::patch('term-results/{termResult}/publish', [TermResultController::class, 'publish'])->name('term-results.publish');
        Route::patch('term-results/{termResult}/unpublish', [TermResultController::class, 'unpublish'])->name('term-results.unpublish');

        Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');

        // Attendance Management (Phase 4) -----------------------------------
        Route::get('attendance', [AdminAttendanceController::class, 'index'])->name('attendance.index');
        Route::get('attendance/mark', [AdminAttendanceController::class, 'mark'])->name('attendance.mark');
        Route::post('attendance/mark', [AdminAttendanceController::class, 'store'])->name('attendance.store');
        Route::get('students/{student}/attendance', [AdminAttendanceController::class, 'forStudent'])->name('students.attendance');
    });

// --- Student ------------------------------------------------------------
Route::middleware(['auth', 'role:student'])
    ->prefix('student')
    ->name('student.')
    ->group(function () {
        Route::get('/dashboard', [StudentDashboard::class, 'index'])->name('dashboard');
        Route::get('/profile', [StudentProfileController::class, 'show'])->name('profile');
        Route::get('/attendance', [StudentAttendanceController::class, 'index'])->name('attendance.index');
        Route::get('/results', [StudentResultController::class, 'index'])->name('results.index');
        Route::get('/results/{termResult}', [StudentResultController::class, 'show'])->name('results.show');
    });

// --- Parent ------------------------------------------------------------
Route::middleware(['auth', 'role:parent'])
    ->prefix('parent')
    ->name('parent.')
    ->group(function () {
        Route::get('/dashboard', [ParentDashboard::class, 'index'])->name('dashboard');
        Route::get('/children', [ChildController::class, 'index'])->name('children.index');
        Route::get('/children/{student}', [ChildController::class, 'show'])->name('children.show');
        Route::get('/children/{student}/results', [ParentResultController::class, 'index'])->name('children.results.index');
        Route::get('/children/{student}/results/{termResult}', [ParentResultController::class, 'show'])->name('children.results.show');
        Route::get('/children/{student}/attendance', [ParentAttendanceController::class, 'index'])->name('children.attendance');
    });
