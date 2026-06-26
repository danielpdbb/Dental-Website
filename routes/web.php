<?php

use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\ReportExportController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Clinic\AllergyController;
use App\Http\Controllers\Clinic\AppointmentController as ClinicAppointmentController;
use App\Http\Controllers\Clinic\AppointmentRecommendationController;
use App\Http\Controllers\Clinic\BillingController;
use App\Http\Controllers\Clinic\ClinicalFindingController;
use App\Http\Controllers\Clinic\ClinicalIntakeController;
use App\Http\Controllers\Clinic\CurrentTreatmentController;
use App\Http\Controllers\PreVisitAssessmentController;
use App\Http\Controllers\Clinic\DentistScheduleController;
use App\Http\Controllers\Clinic\PatientController;
use App\Http\Controllers\Clinic\PaymentController;
use App\Http\Controllers\Clinic\QrPaymentController;
use App\Http\Controllers\Clinic\RecommendationController;
use App\Http\Controllers\Clinic\ReferralController as ClinicReferralController;
use App\Http\Controllers\Clinic\SchedulingController;
use App\Http\Controllers\Clinic\ToothChartController;
use App\Http\Controllers\Clinic\TreatmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Portal\AppointmentController as PortalAppointmentController;
use App\Http\Controllers\Portal\OnlinePaymentController;
use App\Http\Controllers\Portal\RecommendationController as PortalRecommendationController;
use App\Http\Controllers\Portal\RecordController;
use App\Http\Controllers\Portal\ReferralController as PortalReferralController;
use App\Http\Controllers\Portal\RewardController as PortalRewardController;
use App\Http\Controllers\Webhooks\PayMongoController as PayMongoWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public marketing pages
|--------------------------------------------------------------------------
*/
Route::view('/', 'welcome')->name('home');
Route::get('/services', [\App\Http\Controllers\PageController::class, 'services'])->name('services');
Route::view('/about', 'about')->name('about');
Route::view('/contact', 'contact')->name('contact');

// Chat assistant (public, used by the floating widget)
Route::post('/chat', [\App\Http\Controllers\ChatbotController::class, 'reply'])
    ->middleware('throttle:30,1')->name('chat');

// PayMongo webhook (no auth, no CSRF — verified via signature)
Route::post('/webhooks/paymongo', [PayMongoWebhookController::class, 'handle'])->name('webhooks.paymongo');

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

    // Forgot / reset password (link emailed via the clinic-branded notification)
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Profile (all authenticated users)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // In-app notification bell
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'read'])->name('notifications.read');
});

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
| Stage-1 pre-appointment assessment (patient OR staff — authorized in policy)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::post('/appointments/{appointment}/pre-visit', [PreVisitAssessmentController::class, 'save'])
        ->name('appointments.pre-visit.save');
    Route::post('/appointments/{appointment}/pre-visit/{recommendation}/add', [PreVisitAssessmentController::class, 'addSuggested'])
        ->name('appointments.pre-visit.add');
});

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
        Route::get('/analytics/export/appointments.{format}', [ReportExportController::class, 'appointments'])->name('analytics.export');
    });
});

/*
|--------------------------------------------------------------------------
| Patient portal (role: patient)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'role:patient'])->prefix('portal')->name('portal.')->group(function () {
    Route::get('/record', [RecordController::class, 'show'])->name('record');

    // Print a recommendation the dentist accepted & sent
    Route::get('/recommendations/{recommendation}/print', [PortalRecommendationController::class, 'print'])->name('recommendations.print');

    Route::get('/appointments', [PortalAppointmentController::class, 'index'])->name('appointments.index');
    Route::get('/appointments/book', [PortalAppointmentController::class, 'create'])->name('appointments.create');
    Route::post('/appointments', [PortalAppointmentController::class, 'store'])->name('appointments.store');
    Route::get('/appointments/{appointment}/reschedule', [PortalAppointmentController::class, 'reschedule'])->name('appointments.reschedule');
    Route::put('/appointments/{appointment}/reschedule', [PortalAppointmentController::class, 'updateSchedule'])->name('appointments.reschedule.update');
    Route::post('/appointments/{appointment}/cancel', [PortalAppointmentController::class, 'cancel'])->name('appointments.cancel');

    // Printable invoice for a fully-paid visit
    Route::get('/appointments/{appointment}/invoice', [PortalAppointmentController::class, 'invoice'])->name('appointments.invoice');

    // Online payment (PayMongo)
    Route::post('/appointments/{appointment}/pay', [OnlinePaymentController::class, 'checkout'])->name('appointments.pay');
    Route::get('/appointments/{appointment}/pay/success', [OnlinePaymentController::class, 'success'])->name('appointments.pay.success');
    Route::get('/appointments/{appointment}/pay/cancel', [OnlinePaymentController::class, 'cancel'])->name('appointments.pay.cancel');

    // Spend rewards credit on a bill
    Route::post('/appointments/{appointment}/redeem', [PortalRewardController::class, 'redeem'])->name('appointments.redeem');

    Route::get('/referrals', [PortalReferralController::class, 'index'])->name('referrals.index');
    Route::post('/referrals', [PortalReferralController::class, 'store'])->name('referrals.store');

    // Refer a friend — rewards hub
    Route::get('/rewards', [PortalRewardController::class, 'index'])->name('rewards.index');
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
    Route::get('patients/{patient}/treatments/{treatment}/edit', [TreatmentController::class, 'edit'])->name('patients.treatments.edit');
    Route::put('patients/{patient}/treatments/{treatment}', [TreatmentController::class, 'update'])->name('patients.treatments.update');
    Route::delete('patients/{patient}/treatments/{treatment}', [TreatmentController::class, 'destroy'])->name('patients.treatments.destroy');
    Route::post('patients/{patient}/intake', [ClinicalIntakeController::class, 'save'])->name('patients.intake.save');
    Route::post('patients/{patient}/recommendations', [RecommendationController::class, 'store'])->name('patients.recommendations.store');
    Route::get('patients/{patient}/recommendations/download/{format}', [RecommendationController::class, 'download'])->name('patients.recommendations.download');
    Route::get('patients/{patient}/recommendations/{recommendation}/edit', [RecommendationController::class, 'edit'])->name('patients.recommendations.edit');
    Route::put('patients/{patient}/recommendations/{recommendation}', [RecommendationController::class, 'update'])->name('patients.recommendations.update');
    Route::patch('patients/{patient}/recommendations/{recommendation}', [RecommendationController::class, 'updateStatus'])->name('patients.recommendations.status');

    // Dentist's daily schedule (dentists see their own; managers/reception can pick)
    Route::get('my-schedule', [DentistScheduleController::class, 'index'])->name('my-schedule');

    // Current-treatment workspace — dentist (own appts) + management
    Route::get('appointments/{appointment}/treatment', [CurrentTreatmentController::class, 'edit'])->name('appointments.treatment');
    Route::post('appointments/{appointment}/treatment/procedures', [CurrentTreatmentController::class, 'addProcedure'])->name('appointments.treatment.add');
    Route::put('appointments/{appointment}/treatment/procedures/{procedure}', [CurrentTreatmentController::class, 'updateProcedure'])->name('appointments.treatment.update');
    Route::patch('appointments/{appointment}/treatment/procedures/{procedure}', [CurrentTreatmentController::class, 'togglePerformed'])->name('appointments.treatment.toggle');
    Route::delete('appointments/{appointment}/treatment/procedures/{procedure}', [CurrentTreatmentController::class, 'removeProcedure'])->name('appointments.treatment.remove');
    Route::post('appointments/{appointment}/treatment/endorse', [CurrentTreatmentController::class, 'endorse'])->name('appointments.treatment.endorse');

    // Stage-2 clinical findings + recommendation review (dentist own / management via policy)
    Route::post('appointments/{appointment}/findings', [ClinicalFindingController::class, 'save'])->name('appointments.findings.save');
    Route::post('appointments/{appointment}/teeth', [ToothChartController::class, 'store'])->name('appointments.teeth.store');
    Route::delete('appointments/{appointment}/teeth/{fdi}', [ToothChartController::class, 'destroy'])->name('appointments.teeth.destroy');
    Route::put('appointments/{appointment}/recommendations/{recommendation}', [AppointmentRecommendationController::class, 'update'])->name('appointments.recommendations.update');
    Route::post('appointments/{appointment}/recommendations/{recommendation}/accept', [AppointmentRecommendationController::class, 'accept'])->name('appointments.recommendations.accept');
    Route::post('appointments/{appointment}/recommendations/{recommendation}/reject', [AppointmentRecommendationController::class, 'reject'])->name('appointments.recommendations.reject');
    Route::post('appointments/{appointment}/recommendations/{recommendation}/send', [AppointmentRecommendationController::class, 'send'])->name('appointments.recommendations.send');
    Route::get('appointments/{appointment}/recommendations/{recommendation}/print', [AppointmentRecommendationController::class, 'print'])->name('appointments.recommendations.print');

    // Appointment desk — receptionist & management only
    Route::middleware('role:receptionist,management')->group(function () {
        Route::get('appointments', [ClinicAppointmentController::class, 'index'])->name('appointments.index');
        Route::get('appointments/create', [ClinicAppointmentController::class, 'create'])->name('appointments.create');
        Route::post('appointments', [ClinicAppointmentController::class, 'store'])->name('appointments.store');
        Route::get('appointments/{appointment}', [ClinicAppointmentController::class, 'show'])->name('appointments.show');
        Route::post('appointments/{appointment}/cancel', [ClinicAppointmentController::class, 'cancel'])->name('appointments.cancel');
        Route::post('appointments/{appointment}/complete', [ClinicAppointmentController::class, 'complete'])->name('appointments.complete');
        Route::post('appointments/{appointment}/no-show', [ClinicAppointmentController::class, 'noShow'])->name('appointments.no-show');
        Route::put('appointments/{appointment}/reschedule', [ClinicAppointmentController::class, 'reschedule'])->name('appointments.reschedule');
        Route::post('appointments/{appointment}/payment', [PaymentController::class, 'store'])->name('appointments.payment.store');
        Route::post('appointments/{appointment}/redeem-rewards', [PaymentController::class, 'redeemRewards'])->name('appointments.redeem-rewards');

        // Billing queue + statement creation (receptionist / management)
        Route::get('billing', [BillingController::class, 'index'])->name('billing.index');
        Route::post('appointments/{appointment}/billing', [BillingController::class, 'store'])->name('appointments.billing.store');
        Route::get('appointments/{appointment}/billing/print/{type}', [BillingController::class, 'print'])->name('appointments.billing.print');

        // In-store GCash QR (PayMongo QR Ph)
        Route::post('appointments/{appointment}/qr', [QrPaymentController::class, 'generate'])->name('appointments.qr.generate');
        Route::get('appointments/{appointment}/qr/{payment}', [QrPaymentController::class, 'show'])->name('appointments.qr.show');
        Route::get('appointments/{appointment}/qr/{payment}/check', [QrPaymentController::class, 'check'])->name('appointments.qr.check');
        Route::post('appointments/{appointment}/qr/{payment}/cancel', [QrPaymentController::class, 'cancel'])->name('appointments.qr.cancel');

        Route::get('referrals', [ClinicReferralController::class, 'index'])->name('referrals.index');
        Route::patch('referrals/{referral}', [ClinicReferralController::class, 'update'])->name('referrals.update');

        Route::get('scheduling', [SchedulingController::class, 'index'])->name('scheduling');
    });
});
