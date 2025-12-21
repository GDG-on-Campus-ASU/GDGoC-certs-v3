<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminLoginLogController;
use App\Http\Controllers\Admin\AdminOidcController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Middleware\EnsureUserIsAdminOrSuperadmin;
use App\Http\Controllers\Admin\CertificateTemplateController as AdminCertificateTemplateController;
use App\Http\Controllers\Admin\DocumentationController as AdminDocumentationController;
use App\Http\Controllers\Admin\EmailTemplateController as AdminEmailTemplateController;
use App\Http\Controllers\Admin\SmtpProviderController as AdminSmtpProviderController;
use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\BulkCertificateController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CertificateTemplateController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\Leader\CertificateController as LeaderCertificateController;
use App\Http\Controllers\Leader\ConfigurationController;
use App\Http\Controllers\Leader\DashboardController as LeaderDashboardController;
use App\Http\Controllers\Leader\DocumentationController as LeaderDocumentationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicCertificateController;
use App\Http\Controllers\PublicTemplatePreviewController;
use App\Http\Controllers\SmtpProviderController;
use App\Http\Middleware\EnsureUserIsSuperadmin;
use Illuminate\Support\Facades\Route;

// Public validation page (certs.gdg-oncampus.dev) - accessible via configured public domain
Route::domain(config('domains.public', 'certs.gdg-oncampus.dev'))
    ->middleware('throttle:60,1')
    ->group(function () {
        Route::get('/', [PublicCertificateController::class, 'index'])->name('public.validate.index.domain');
        Route::get('/validate', [PublicCertificateController::class, 'validate'])->name('public.validate.query.domain');
        Route::get('/c/{unique_id}', [PublicCertificateController::class, 'show'])->name('public.certificate.show.domain');
        Route::get('/c/{unique_id}/download', [PublicCertificateController::class, 'download'])->name('public.certificate.download.domain');

        // Public Template Preview
        Route::post('/preview/certificate', [PublicTemplatePreviewController::class, 'previewCertificate'])->name('public.preview.certificate.domain');
        Route::post('/preview/email', [PublicTemplatePreviewController::class, 'previewEmail'])->name('public.preview.email.domain');
    });

// Admin dashboard (sudo.certs-admin.certs.gdg-oncampus.dev)
Route::domain(config('domains.admin', 'sudo.certs-admin.certs.gdg-oncampus.dev'))
    ->middleware(['auth', 'org_name'])
    ->group(function () {
        // OAuth / OIDC Routes
        Route::get('/auth/redirect', [OAuthController::class, 'redirect'])->name('oauth.redirect')->withoutMiddleware(['auth', 'org_name']);
        Route::get('/auth/callback', [OAuthController::class, 'callback'])->name('oauth.callback')->withoutMiddleware(['auth', 'org_name']);

        // Leader Routes - Dashboard
        Route::get('/dashboard', [LeaderDashboardController::class, 'index'])->name('dashboard');

        // Leader Routes - Protected by auth middleware
        Route::prefix('dashboard')->name('dashboard.')->group(function () {
            // Certificate Templates
            Route::post('/templates/certificates/preview', [CertificateTemplateController::class, 'preview'])->name('templates.certificates.preview');
            Route::post('/templates/certificates/{certificateTemplate}/clone', [CertificateTemplateController::class, 'clone'])->name('templates.certificates.clone');
            Route::post('/templates/certificates/{certificateTemplate}/reset', [CertificateTemplateController::class, 'reset'])->name('templates.certificates.reset');
            Route::resource('templates/certificates', CertificateTemplateController::class)->names('templates.certificates');

            // Email Templates
            Route::post('/templates/email/preview', [EmailTemplateController::class, 'preview'])->name('templates.email.preview');
            Route::post('/templates/email/{emailTemplate}/clone', [EmailTemplateController::class, 'clone'])->name('templates.email.clone');
            Route::post('/templates/email/{emailTemplate}/reset', [EmailTemplateController::class, 'reset'])->name('templates.email.reset');
            Route::resource('templates/email', EmailTemplateController::class)->names('templates.email');

            // Certificates
            Route::get('/certificates/create', [CertificateController::class, 'create'])->name('certificates.create');
            Route::post('/certificates', [CertificateController::class, 'store'])->name('certificates.store');

            // Bulk Certificates
            Route::get('/certificates/bulk', [BulkCertificateController::class, 'create'])->name('certificates.bulk');
            Route::post('/certificates/bulk', [BulkCertificateController::class, 'store'])->name('certificates.bulk.store');

            // Certificate Management
            Route::get('/certificates', [LeaderCertificateController::class, 'index'])->name('certificates.index');
            Route::post('/certificates/{certificate}/revoke', [LeaderCertificateController::class, 'revoke'])->name('certificates.revoke');

            // SMTP Providers
            Route::resource('smtp', SmtpProviderController::class)->names('smtp');

            // Configuration
            Route::get('/configuration', [ConfigurationController::class, 'index'])->name('configuration.index');

            // Documentation
            Route::get('/documentation', [LeaderDocumentationController::class, 'index'])->name('documentation.index');
            Route::get('/documentation/{documentation:slug}', [LeaderDocumentationController::class, 'show'])->name('documentation.show');
        });

        // Admin and Superadmin Routes
        Route::middleware(EnsureUserIsAdminOrSuperadmin::class)
            ->prefix('admin')
            ->name('admin.')
            ->group(function () {
                Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

                // User Management - accessible by both admin and superadmin
                Route::resource('users', AdminUserController::class)->except(['show']);

                // Superadmin-only routes
                Route::middleware(EnsureUserIsSuperadmin::class)->group(function () {
                    // Template Management
                    Route::post('/templates/certificates/preview', [AdminCertificateTemplateController::class, 'preview'])->name('templates.certificates.preview');
                    Route::resource('templates/certificates', AdminCertificateTemplateController::class)->names('templates.certificates');

                    Route::post('/templates/email/preview', [AdminEmailTemplateController::class, 'preview'])->name('templates.email.preview');
                    Route::resource('templates/email', AdminEmailTemplateController::class)->names('templates.email');

                    // OIDC Settings
                    Route::get('/settings/oidc', [AdminOidcController::class, 'edit'])->name('oidc.edit');
                    Route::post('/settings/oidc', [AdminOidcController::class, 'update'])->name('oidc.update');

                    // Login Logs
                    Route::get('/logs/logins', [AdminLoginLogController::class, 'index'])->name('logs.index');
                    Route::get('/logs/feed', [AdminLoginLogController::class, 'feed'])->name('logs.feed');

                    // Documentation Management
                    Route::resource('documentation', AdminDocumentationController::class);
                });
            });

        // Profile routes (on admin domain)
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit.admin');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update.admin');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy.admin');
        // Profile routes (on admin domain) - without org_name middleware to allow completion
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit.admin')->withoutMiddleware(['org_name']);
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update.admin')->withoutMiddleware(['org_name']);
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy.admin')->withoutMiddleware(['org_name']);
    });

// Add a root route for the admin domain to redirect to login
Route::domain(config('domains.admin', 'sudo.certs-admin.certs.gdg-oncampus.dev'))
    ->group(function () {
        Route::get('/', function () {
            return redirect()->route('login');
        });
    });

// Auth routes - available globally for testing and non-domain access
require __DIR__.'/auth.php';

// Non-domain routes for testing and local development
Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [LeaderDashboardController::class, 'index'])->middleware(['auth', 'org_name'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

// OAuth / OIDC Routes (non-domain fallback)
Route::get('/auth/redirect', [OAuthController::class, 'redirect'])->name('oauth.redirect.fallback');
Route::get('/auth/callback', [OAuthController::class, 'callback'])->name('oauth.callback.fallback');

// Leader Routes - Protected by auth middleware (non-domain fallback)
Route::middleware(['auth', 'org_name'])->prefix('dashboard')->name('dashboard.')->group(function () {
    // Certificate Templates
    Route::post('/templates/certificates/preview', [CertificateTemplateController::class, 'preview'])->name('templates.certificates.preview');
    Route::post('/templates/certificates/{certificateTemplate}/clone', [CertificateTemplateController::class, 'clone'])->name('templates.certificates.clone');
    Route::post('/templates/certificates/{certificateTemplate}/reset', [CertificateTemplateController::class, 'reset'])->name('templates.certificates.reset');
    Route::resource('templates/certificates', CertificateTemplateController::class)->names('templates.certificates');

    // Email Templates
    Route::post('/templates/email/preview', [EmailTemplateController::class, 'preview'])->name('templates.email.preview');
    Route::post('/templates/email/{emailTemplate}/clone', [EmailTemplateController::class, 'clone'])->name('templates.email.clone');
    Route::post('/templates/email/{emailTemplate}/reset', [EmailTemplateController::class, 'reset'])->name('templates.email.reset');
    Route::resource('templates/email', EmailTemplateController::class)->names('templates.email');

    // Certificates
    Route::get('/certificates/create', [CertificateController::class, 'create'])->name('certificates.create');
    Route::post('/certificates', [CertificateController::class, 'store'])->name('certificates.store');

    // Bulk Certificates
    Route::get('/certificates/bulk', [BulkCertificateController::class, 'create'])->name('certificates.bulk');
    Route::post('/certificates/bulk', [BulkCertificateController::class, 'store'])->name('certificates.bulk.store');

    // Certificate Management
    Route::get('/certificates', [LeaderCertificateController::class, 'index'])->name('certificates.index');
    Route::post('/certificates/{certificate}/revoke', [LeaderCertificateController::class, 'revoke'])->name('certificates.revoke');

    // SMTP Providers
    Route::resource('smtp', SmtpProviderController::class)->names('smtp');

    // Configuration
    Route::get('/configuration', [ConfigurationController::class, 'index'])->name('configuration.index');

    // Documentation
    Route::get('/documentation', [LeaderDocumentationController::class, 'index'])->name('documentation.index');
    Route::get('/documentation/{documentation:slug}', [LeaderDocumentationController::class, 'show'])->name('documentation.show');
});

// Admin Routes - Protected by auth and admin_or_superadmin middleware (non-domain fallback)
Route::middleware(['auth', 'org_name', 'admin_or_superadmin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // User Management - accessible by both admin and superadmin
    Route::resource('users', AdminUserController::class)->except(['show']);

    // Superadmin-only routes
    Route::middleware('superadmin')->group(function () {
        // Template Management
        Route::resource('templates/certificates', AdminCertificateTemplateController::class)->names('templates.certificates');
        Route::resource('templates/email', AdminEmailTemplateController::class)->names('templates.email');

        // Global SMTP Management
        Route::resource('smtp', AdminSmtpProviderController::class)->names('smtp');

        // OIDC Settings
        Route::get('/settings/oidc', [AdminOidcController::class, 'edit'])->name('oidc.edit');
        Route::post('/settings/oidc', [AdminOidcController::class, 'update'])->name('oidc.update');

        // Login Logs
        Route::get('/logs/logins', [AdminLoginLogController::class, 'index'])->name('logs.index');
        Route::get('/logs/feed', [AdminLoginLogController::class, 'feed'])->name('logs.feed');

        // Documentation Management
        Route::resource('documentation', AdminDocumentationController::class);
    });
});

// Public Certificate Validation Routes - Non-domain fallback (accessible from any domain)
Route::prefix('public')->middleware('throttle:60,1')->group(function () {
    Route::get('/', [PublicCertificateController::class, 'index'])->name('public.validate.index');
    Route::get('/validate', [PublicCertificateController::class, 'validate'])->name('public.validate.query');
    Route::get('/c/{unique_id}', [PublicCertificateController::class, 'show'])->name('public.certificate.show');
    Route::get('/c/{unique_id}/download', [PublicCertificateController::class, 'download'])->name('public.certificate.download');

    // Public Template Preview
    Route::post('/preview/certificate', [PublicTemplatePreviewController::class, 'previewCertificate'])->name('public.preview.certificate');
    Route::post('/preview/email', [PublicTemplatePreviewController::class, 'previewEmail'])->name('public.preview.email');
});

// Public Certificate Validation Routes - Legacy domain-based routes
// Note: Uses VALIDATION_DOMAIN env variable (deprecated) for backward compatibility with older configs
// Newer configs should use DOMAIN_PUBLIC instead
Route::domain(config('domains.validation'))->middleware('throttle:60,1')->group(function () {
    Route::get('/', [PublicCertificateController::class, 'index'])->name('public.validate.index.legacy');
    Route::get('/validate', [PublicCertificateController::class, 'validate'])->name('public.validate.query.legacy');
    Route::get('/c/{unique_id}', [PublicCertificateController::class, 'show'])->name('public.certificate.show.legacy');
    Route::get('/c/{unique_id}/download', [PublicCertificateController::class, 'download'])->name('public.certificate.download.legacy');
});


