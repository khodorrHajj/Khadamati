<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\MunicipalityController;
use App\Http\Controllers\CitizenController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/signup', [LoginController::class, 'signup'])->name('signup');
Route::post('/register', [LoginController::class, 'register'])->name('register');

Route::get('/login', [LoginController::class, 'login'])->name('login');
Route::post('/dologin', [LoginController::class, 'doLogin'])->name('dologin');

Route::get('/auth/google', [LoginController::class, 'redirectToGoogle'])->name('google.redirect');
Route::get('/auth/google/callback', [LoginController::class, 'handleGoogleCallback'])->name('google.callback');

Route::middleware('check2fa')->group(function () {
    Route::get('/2fa', [LoginController::class, 'twoFactorForm'])->name('twofactor.form');
    Route::post('/2fa', [LoginController::class, 'verifyTwoFactor'])->name('twofactor.verify');
    Route::post('/2fa/resend', [LoginController::class, 'resendTwoFactor'])->name('twofactor.resend');
});

Route::middleware('checkIfConnected')->group(function () {
    Route::get('/home', [LoginController::class, 'home'])->name('home');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});

Route::middleware('checkIfConnected')->group(function () {
    Route::get('/home', [LoginController::class, 'home'])->name('home');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});

Route::middleware(['checkIfConnected', 'checkRole:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

        Route::get('/municipalities', [AdminController::class, 'municipalities'])->name('municipalities');
        Route::post('/municipalities', [AdminController::class, 'storeMunicipality'])->name('municipalities.store');

        Route::get('/offices', [AdminController::class, 'offices'])->name('offices');
        Route::post('/offices', [AdminController::class, 'storeOffice'])->name('offices.store');

        Route::get('/municipality-users', [AdminController::class, 'municipalityUsers'])->name('municipality.users');
        Route::post('/municipality-users', [AdminController::class, 'storeMunicipalityUser'])->name('municipality.users.store');
    });

Route::middleware(['checkIfConnected', 'checkRole:municipality'])
    ->prefix('municipality')
    ->name('municipality.')
    ->group(function () {
        Route::get('/dashboard', [MunicipalityController::class, 'dashboard'])->name('dashboard');

        Route::get('/categories', [MunicipalityController::class, 'categories'])->name('categories');
        Route::post('/categories', [MunicipalityController::class, 'storeCategory'])->name('categories.store');

        Route::get('/services', [MunicipalityController::class, 'services'])->name('services');
        Route::post('/services', [MunicipalityController::class, 'storeService'])->name('services.store');
    });

Route::middleware(['checkIfConnected', 'checkRole:citizen'])
    ->prefix('citizen')
    ->name('citizen.')
    ->group(function () {
        Route::get('/dashboard', [CitizenController::class, 'dashboard'])->name('dashboard');
    });

