<?php

use App\Http\Controllers\AuditExportController;
use App\Http\Controllers\FileDownloadController;
use App\Http\Controllers\SessionController;
use App\Livewire\Academic;
use App\Livewire\Academics;
use App\Livewire\Account\NotificationPreferences;
use App\Livewire\Account\Profile;
use App\Livewire\Account\Security;
use App\Livewire\Account\StaffProfile;
use App\Livewire\Admin;
use App\Livewire\Admissions;
use App\Livewire\Attendance;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\RequiredPasswordChange;
use App\Livewire\Auth\RequiredTwoStepSetup;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Auth\SetupAccount;
use App\Livewire\Auth\TwoStepChallenge;
use App\Livewire\Auth\VerifyEmail;
use App\Livewire\Calendar;
use App\Livewire\Home;
use App\Livewire\Hr;
use App\Livewire\Notifications;
use App\Livewire\Platform;
use App\Livewire\Students;
use App\Livewire\Timetable;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| CampusOS web routes
|--------------------------------------------------------------------------
| Guests: sign-in and recovery. Signed in: required account steps, then
| everything else behind campus.ready (no pending requirement).
*/

Route::middleware('campus.guest')->group(function (): void {
    Route::livewire('login', Login::class)->name('login');
    Route::livewire('login/verify', TwoStepChallenge::class)->name('login.verify');
    Route::livewire('forgot-password', ForgotPassword::class)->name('password.forgot');
    Route::livewire('reset-password', ResetPassword::class)->name('password.reset');
    Route::livewire('setup-account', SetupAccount::class)->name('account.setup');
    Route::livewire('register/{institution}', Register::class)->where('institution', '[A-Za-z0-9_-]{2,32}')->name('register');
});

Route::middleware(['campus.auth'])->group(function (): void {
    Route::post('logout', [SessionController::class, 'logout'])->name('logout');
    Route::post('institution/switch', [SessionController::class, 'switchInstitution'])->name('institution.switch');

    // Steps that must be completed before using the app (see EnsureAccountReady).
    Route::livewire('required/email', VerifyEmail::class)->name('required.email');
    Route::livewire('required/password', RequiredPasswordChange::class)->name('required.password');
    Route::livewire('required/two-step', RequiredTwoStepSetup::class)->name('required.two-step');

    Route::middleware('campus.ready')->group(function (): void {
        Route::livewire('/', Home::class)->name('home');
        Route::livewire('notifications', Notifications::class)->name('notifications.index');
        Route::livewire('calendar', Calendar::class)->name('calendar.index');

        Route::prefix('account')->name('account.')->group(function (): void {
            Route::livewire('/', Profile::class)->name('profile');
            Route::livewire('security', Security::class)->name('security');
            Route::livewire('notifications', NotificationPreferences::class)->name('notifications');
            Route::livewire('staff-record', StaffProfile::class)->name('staff-profile');
        });

        Route::get('files/{file}/download', FileDownloadController::class)->where('file', '[0-9A-Za-z]{26}')->name('files.download');

        // Academic structure: lists are open to members; changes need the manage permission.
        Route::livewire('departments', Academic\Departments\Index::class)->name('departments.index');
        Route::livewire('programmes', Academic\Programmes\Index::class)->name('programmes.index');
        Route::livewire('batches', Academic\Batches\Index::class)->name('batches.index');
        Route::livewire('rooms', Academic\Rooms\Index::class)->name('rooms.index');
        Route::livewire('academic-years', Academic\AcademicYears\Index::class)->middleware('permission:institution.calendar.manage')->name('academic-years.index');

        // Staff
        Route::middleware('permission:hr.employees.view')->group(function (): void {
            Route::livewire('staff', Hr\Staff\Index::class)->name('staff.index');
            Route::livewire('staff/{employee}', Hr\Staff\Show::class)->name('staff.show');
        });
        Route::livewire('designations', Hr\Designations\Index::class)->middleware('permission:hr.designations.manage')->name('designations.index');

        // Admissions: applicants see only their own applications (checked by the API).
        Route::livewire('my-applications', Admissions\MyApplications\Index::class)->name('my-applications.index');
        Route::livewire('my-applications/{application}', Admissions\MyApplications\Show::class)->name('my-applications.show');
        Route::middleware('permission:admissions.applications.view')->group(function (): void {
            Route::livewire('applications', Admissions\Applications\Index::class)->name('applications.index');
            Route::livewire('applications/{application}', Admissions\Applications\Show::class)->name('applications.show');
        });
        Route::middleware('permission:admissions.cycles.manage')->group(function (): void {
            Route::livewire('admission-cycles', Admissions\Cycles\Index::class)->name('admission-cycles.index');
            Route::livewire('admission-cycles/{cycle}', Admissions\Cycles\Show::class)->name('admission-cycles.show');
        });

        // Curriculum and teaching (class pages check access in the API)
        Route::livewire('my-classes', Academics\MyClasses::class)->name('my-classes.index');
        Route::livewire('classes/{class}', Academics\ClassShow::class)->name('classes.show');
        Route::livewire('my-registration', Academics\MyRegistration::class)->name('my-registration.show');
        Route::livewire('term-registrations', Academics\Registrations::class)->middleware('permission:academics.registrations.approve')->name('term-registrations.index');
        Route::middleware('permission:academics.catalogue.view')->group(function (): void {
            Route::livewire('courses', Academics\Courses\Index::class)->name('courses.index');
            Route::livewire('courses/{course}', Academics\Courses\Show::class)->name('courses.show');
            Route::livewire('curricula', Academics\Curricula\Index::class)->name('curricula.index');
            Route::livewire('curricula/{curriculum}', Academics\Curricula\Show::class)->name('curricula.show');
            Route::livewire('course-offerings', Academics\Offerings\Index::class)->name('course-offerings.index');
            Route::livewire('faculty-workload', Academics\Workload::class)->name('faculty-workload.index');
        });

        // Attendance (class pages check access in the API)
        Route::livewire('attendance', Attendance\Take::class)->middleware('permission:attendance.records.mark,attendance.records.edit')->name('attendance.take');
        Route::livewire('attendance/classes/{class}', Attendance\ClassAttendance::class)->name('attendance.class');
        Route::livewire('my-attendance', Attendance\MyAttendance::class)->name('my-attendance.show');
        Route::livewire('attendance-shortage', Attendance\Shortage::class)->middleware('permission:attendance.reports.view')->name('attendance-shortage.index');
        Route::livewire('attendance-corrections', Attendance\Corrections::class)->middleware('permission:attendance.corrections.approve')->name('attendance-corrections.index');

        // Timetable
        Route::livewire('my-timetable', Timetable\MyTimetable::class)->name('my-timetable.show');
        Route::middleware('permission:timetable.view')->group(function (): void {
            Route::livewire('timetables', Timetable\Index::class)->name('timetables.index');
            Route::livewire('timetables/{timetable}', Timetable\Builder::class)->name('timetables.show');
            Route::livewire('timetable-view', Timetable\Viewer::class)->name('timetable-view.index');
        });
        Route::livewire('periods', Timetable\Periods::class)->middleware('permission:timetable.manage')->name('periods.index');

        // Students
        Route::livewire('my-record', Students\MyRecord::class)->name('my-record.show');
        Route::middleware('permission:students.records.view')->group(function (): void {
            Route::livewire('students', Students\Index::class)->name('students.index');
            Route::livewire('students/{student}', Students\Show::class)->name('students.show');
        });
        Route::livewire('student-change-requests', Students\ChangeRequests::class)->middleware('permission:students.changes.approve')->name('student-change-requests.index');

        // Administration
        Route::prefix('admin')->group(function (): void {
            Route::middleware('permission:identity.users.view')->group(function (): void {
                Route::livewire('users', Admin\Users\Index::class)->name('users.index');
                Route::livewire('users/{user}', Admin\Users\Show::class)->name('users.show');
            });
            Route::livewire('roles', Admin\Roles\Index::class)->middleware('permission:identity.roles.view')->name('roles.index');
            Route::livewire('institution', Admin\Institution\Edit::class)->middleware('permission:institution.profile.manage')->name('institution.edit');
            Route::livewire('settings', Admin\Settings\Index::class)->middleware('permission:admin.settings.view')->name('settings.index');
            Route::livewire('templates', Admin\Templates\Index::class)->middleware('permission:communication.templates.manage,communication.deliveries.view')->name('templates.index');
            Route::livewire('audit', Admin\Audit\Index::class)->middleware('permission:admin.audit.view,identity.security_events.view')->name('audit.index');
            Route::get('audit/export', AuditExportController::class)->middleware('permission:admin.audit.export')->name('audit.export');
            Route::livewire('imports', Admin\Imports\Index::class)->middleware('permission:identity.users.import,hr.employees.import,students.records.import')->name('imports.index');
            Route::livewire('health', Admin\Health\Index::class)->middleware('permission:admin.monitoring.view')->name('health.index');
        });

        // Platform administrators only
        Route::prefix('platform')->name('platform.')->middleware('permission:platform')->group(function (): void {
            Route::livewire('institutions', Platform\Institutions\Index::class)->name('institutions.index');
            Route::livewire('settings', Platform\Settings::class)->name('settings.index');
            Route::livewire('monitoring', Platform\Monitoring::class)->name('monitoring.index');
        });
    });
});

// Local component catalogue (never in production).
if (config('campusos.style_guide')) {
    Route::view('_ui', 'pages.style-guide')->name('style-guide');
}
