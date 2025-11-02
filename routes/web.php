<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminLoginLogController;
use App\Http\Controllers\Admin\AdminOidcController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\CertificateTemplateController as AdminCertificateTemplateController;
use App\Http\Controllers\Admin\EmailTemplateController as AdminEmailTemplateController;
use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\BulkCertificateController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CertificateTemplateController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SmtpProviderController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// OAuth / OIDC Routes
Route::get('/auth/redirect', [OAuthController::class, 'redirect'])->name('oauth.redirect');
Route::get('/auth/callback', [OAuthController::class, 'callback'])->name('oauth.callback');

// Leader Routes - Protected by auth middleware
Route::middleware(['auth'])->prefix('dashboard')->name('dashboard.')->group(function () {
    // Certificate Templates
    Route::post('/templates/certificates/{certificateTemplate}/clone', [CertificateTemplateController::class, 'clone'])->name('templates.certificates.clone');
    Route::post('/templates/certificates/{certificateTemplate}/reset', [CertificateTemplateController::class, 'reset'])->name('templates.certificates.reset');
    Route::resource('templates/certificates', CertificateTemplateController::class)->names('templates.certificates');

    // Email Templates
    Route::post('/templates/email/{emailTemplate}/clone', [EmailTemplateController::class, 'clone'])->name('templates.email.clone');
    Route::post('/templates/email/{emailTemplate}/reset', [EmailTemplateController::class, 'reset'])->name('templates.email.reset');
    Route::resource('templates/email', EmailTemplateController::class)->names('templates.email');

    // Certificates
    Route::get('/certificates/create', [CertificateController::class, 'create'])->name('certificates.create');
    Route::post('/certificates', [CertificateController::class, 'store'])->name('certificates.store');

    // Bulk Certificates
    Route::get('/certificates/bulk', [BulkCertificateController::class, 'create'])->name('certificates.bulk');
    Route::post('/certificates/bulk', [BulkCertificateController::class, 'store'])->name('certificates.bulk.store');

    // SMTP Providers
    Route::resource('smtp', SmtpProviderController::class)->names('smtp');
});

// Admin Routes - Protected by auth and superadmin middleware
Route::middleware(['auth', 'superadmin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // User Management
    Route::resource('users', AdminUserController::class)->except(['show']);

    // Template Management
    Route::resource('templates/certificates', AdminCertificateTemplateController::class)->names('templates.certificates');
    Route::resource('templates/email', AdminEmailTemplateController::class)->names('templates.email');

    // OIDC Settings
    Route::get('/settings/oidc', [AdminOidcController::class, 'edit'])->name('oidc.edit');
    Route::post('/settings/oidc', [AdminOidcController::class, 'update'])->name('oidc.update');

    // Login Logs
    Route::get('/logs/logins', [AdminLoginLogController::class, 'index'])->name('logs.index');
    Route::get('/logs/feed', [AdminLoginLogController::class, 'feed'])->name('logs.feed');
});

require __DIR__.'/auth.php';
