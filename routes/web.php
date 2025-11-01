<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminLoginLogController;
use App\Http\Controllers\Admin\AdminOidcController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\ProfileController;
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

// Admin Routes - Protected by auth and superadmin middleware
Route::middleware(['auth', 'superadmin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // User Management
    Route::resource('users', AdminUserController::class)->except(['show']);

    // OIDC Settings
    Route::get('/settings/oidc', [AdminOidcController::class, 'edit'])->name('oidc.edit');
    Route::post('/settings/oidc', [AdminOidcController::class, 'update'])->name('oidc.update');

    // Login Logs
    Route::get('/logs/logins', [AdminLoginLogController::class, 'index'])->name('logs.index');
    Route::get('/logs/feed', [AdminLoginLogController::class, 'feed'])->name('logs.feed');
});

require __DIR__.'/auth.php';
