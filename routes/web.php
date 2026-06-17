<?php

use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Clinic\AllergyController;
use App\Http\Controllers\Clinic\AppointmentController as ClinicAppointmentController;
use App\Http\Controllers\Clinic\PatientController;
use App\Http\Controllers\Clinic\PaymentController;
use App\Http\Controllers\Clinic\RecommendationController;
use App\Http\Controllers\Clinic\ReferralController as ClinicReferralController;
use App\Http\Controllers\Clinic\SchedulingController;
use App\Http\Controllers\Clinic\TreatmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Portal\AppointmentController as PortalAppointmentController;
use App\Http\Controllers\Portal\RecordController;
use App\Http\Controllers\Portal\ReferralController as PortalReferralController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public marketing pages
|--------------------------------------------------------------------------
*/
Route::view('/', 'welcome')->name('home');
Route::view('/services', 'services')->name('services');
Route::view('/about', 'about')->name('about');
Route::view('/contact', 'contact')->name('contact');

/*
|--------------------------------------------------------------------------
| Guest authentication (patients & staff)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Email verification
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])
        ->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

/*
|--------------------------------------------------------------------------
| Patient / staff dashboard (must be verified)
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

/*
|--------------------------------------------------------------------------
| Admin area
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {
    // Dedicated admin login (Management only)
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AdminAuthController::class, 'create'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'store']);
    });

    Route::middleware(['auth', 'role:management'])->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('logout');
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::resource('users', AdminUserController::class)->except('show');
        Route::resource('services', ServiceController::class)->except('show');
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
    });
});

/*
|--------------------------------------------------------------------------
| Patient portal (role: patient)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'role:patient'])->prefix('portal')->name('portal.')->group(function () {
    Route::get('/record', [RecordController::class, 'show'])->name('record');

    Route::get('/appointments', [PortalAppointmentController::class, 'index'])->name('appointments.index');
    Route::get('/appointments/book', [PortalAppointmentController::class, 'create'])->name('appointments.create');
    Route::post('/appointments', [PortalAppointmentController::class, 'store'])->name('appointments.store');
    Route::post('/appointments/{appointment}/cancel', [PortalAppointmentController::class, 'cancel'])->name('appointments.cancel');

    Route::get('/referrals', [PortalReferralController::class, 'index'])->name('referrals.index');
    Route::post('/referrals', [PortalReferralController::class, 'store'])->name('referrals.store');
});

/*
|--------------------------------------------------------------------------
| Clinic back-office (staff: receptionist, dentist, management)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:receptionist,dentist,management'])->prefix('clinic')->name('clinic.')->group(function () {
    // Patient records — dentist, receptionist, management
    Route::resource('patients', PatientController::class);
    Route::post('patients/{patient}/allergies', [AllergyController::class, 'store'])->name('patients.allergies.store');
    Route::delete('patients/{patient}/allergies/{allergy}', [AllergyController::class, 'destroy'])->name('patients.allergies.destroy');
    Route::post('patients/{patient}/treatments', [TreatmentController::class, 'store'])->name('patients.treatments.store');
    Route::delete('patients/{patient}/treatments/{treatment}', [TreatmentController::class, 'destroy'])->name('patients.treatments.destroy');
    Route::post('patients/{patient}/recommendations', [RecommendationController::class, 'store'])->name('patients.recommendations.store');
    Route::patch('patients/{patient}/recommendations/{recommendation}', [RecommendationController::class, 'updateStatus'])->name('patients.recommendations.status');

    // Appointment desk — receptionist & management only
    Route::middleware('role:receptionist,management')->group(function () {
        Route::get('appointments', [ClinicAppointmentController::class, 'index'])->name('appointments.index');
        Route::get('appointments/create', [ClinicAppointmentController::class, 'create'])->name('appointments.create');
        Route::post('appointments', [ClinicAppointmentController::class, 'store'])->name('appointments.store');
        Route::get('appointments/{appointment}', [ClinicAppointmentController::class, 'show'])->name('appointments.show');
        Route::post('appointments/{appointment}/cancel', [ClinicAppointmentController::class, 'cancel'])->name('appointments.cancel');
        Route::post('appointments/{appointment}/complete', [ClinicAppointmentController::class, 'complete'])->name('appointments.complete');
        Route::post('appointments/{appointment}/no-show', [ClinicAppointmentController::class, 'noShow'])->name('appointments.no-show');
        Route::post('appointments/{appointment}/payment', [PaymentController::class, 'store'])->name('appointments.payment.store');

        Route::get('referrals', [ClinicReferralController::class, 'index'])->name('referrals.index');
        Route::patch('referrals/{referral}', [ClinicReferralController::class, 'update'])->name('referrals.update');

        Route::get('scheduling', [SchedulingController::class, 'index'])->name('scheduling');
    });
});
