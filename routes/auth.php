<?php

use App\Http\Controllers\Citizen\StripePaymentController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\IdentityVerificationController;
use App\Http\Controllers\RequestMessageAttachmentController;
use App\Http\Controllers\TrackingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/signup', [LoginController::class, 'signup'])->name('signup');
Route::post('/register', [LoginController::class, 'register'])->name('register');

Route::get('/login', [LoginController::class, 'login'])->name('login');
Route::post('/dologin', [LoginController::class, 'doLogin'])->name('dologin');

Route::get('/auth/google', [LoginController::class, 'redirectToGoogle'])->name('google.redirect');
Route::get('/auth/google/callback', [LoginController::class, 'handleGoogleCallback'])->name('google.callback');
Route::get('/track/{trackingCode}', [TrackingController::class, 'show'])->name('tracking.show');

Route::post('/webhook/stripe', [StripePaymentController::class, 'webhook'])->name('webhook.stripe');

Route::middleware('check2fa')->group(function () {
    Route::get('/2fa', [LoginController::class, 'twoFactorForm'])->name('twofactor.form');
    Route::post('/2fa', [LoginController::class, 'verifyTwoFactor'])->name('twofactor.verify');
    Route::post('/2fa/resend', [LoginController::class, 'resendTwoFactor'])->name('twofactor.resend');
});

Route::middleware('checkIfConnected')->group(function () {
    Route::get('/home', [LoginController::class, 'home'])->name('home');
    Route::get('/request-messages/{requestMessage}/attachment/download', [RequestMessageAttachmentController::class, 'download'])
        ->name('request-messages.attachments.download');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/identity-verification', [IdentityVerificationController::class, 'create'])->name('identity.verification.create');
    Route::get('/identity-verification/status', [IdentityVerificationController::class, 'status'])->name('identity.verification.status');
    Route::post('/identity-verification', [IdentityVerificationController::class, 'store'])->name('identity.verification.store');
});
