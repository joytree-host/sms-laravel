<?php

namespace App\Providers;

use App\Models\Enrollment;
use App\Models\ParentGuardian;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Assessment;
use App\Models\AssessmentType;
use App\Models\GradeBand;
use App\Models\Term;
use App\Models\TermResult;
use App\Models\Setting;
use App\Models\Attendance;
use App\Policies\EnrollmentPolicy;
use App\Policies\ParentPolicy;
use App\Policies\SchoolClassPolicy;
use App\Policies\StudentPolicy;
use App\Policies\SubjectPolicy;
use App\Policies\AssessmentPolicy;
use App\Policies\AssessmentTypePolicy;
use App\Policies\GradeBandPolicy;
use App\Policies\TermPolicy;
use App\Policies\TermResultPolicy;
use App\Policies\SettingPolicy;
use App\Policies\AttendancePolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Registers the `login` rate limiter used by routes/web.php, plus (as of
 * Phase 2) every model Policy. Laravel 11's slim skeleton has no dedicated
 * AuthServiceProvider, so Gate::policy() calls live here — this is now the
 * single place both "who can log in how fast" and "who can touch which
 * record" are wired up, mirroring how bootstrap/app.php is the single place
 * the `role:` middleware alias is registered.
 */
class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $emailKey = Str::transliterate(Str::lower((string) $request->input('email')));

            return Limit::perMinute(5)->by($emailKey . '|' . $request->ip());
        });

        Gate::policy(Student::class, StudentPolicy::class);
        Gate::policy(ParentGuardian::class, ParentPolicy::class);
        Gate::policy(SchoolClass::class, SchoolClassPolicy::class);
        Gate::policy(Subject::class, SubjectPolicy::class);
        Gate::policy(Enrollment::class, EnrollmentPolicy::class);

        // Phase 3 — Assessment & Results Management
        Gate::policy(Assessment::class, AssessmentPolicy::class);
        Gate::policy(AssessmentType::class, AssessmentTypePolicy::class);
        Gate::policy(GradeBand::class, GradeBandPolicy::class);
        Gate::policy(Term::class, TermPolicy::class);
        Gate::policy(TermResult::class, TermResultPolicy::class);
        Gate::policy(Setting::class, SettingPolicy::class);

        // Phase 4 — Attendance Management
        Gate::policy(Attendance::class, AttendancePolicy::class);
    }
}
