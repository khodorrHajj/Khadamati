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

// Temporary testing route. Remove when admin testing is finished.
Route::get('/fake-admin-login', function () {
    $adminRole = \App\Models\Role::where('role', 'admin')->first();

    if (!$adminRole) {
        abort(500, 'Admin role does not exist.');
    }

    $admin = \App\Models\User::where('role_id', $adminRole->id)->first();

    if (!$admin) {
        $admin = \App\Models\User::create([
            'name' => 'Fake Admin',
            'email' => 'fake.admin@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role_id' => $adminRole->id,
            'is_active' => true,
            'two_factor_enabled' => false,
        ]);
    }

    \Illuminate\Support\Facades\Auth::login($admin);
    request()->session()->regenerate();

    return redirect()->route('admin.dashboard');
})->name('fake.admin.login');

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

        Route::resource('municipalities', \App\Http\Controllers\Admin\MunicipalityController::class)->names([
        'index'   => 'municipalities.index',
        'create'  => 'municipalities.create',
        'store'   => 'municipalities.store',
        'show'    => 'municipalities.show',
        'edit'    => 'municipalities.edit',
        'update'  => 'municipalities.update',
        'destroy' => 'municipalities.destroy',
    ]);

        Route::get('/offices', [\App\Http\Controllers\Admin\GovernmentOfficeController::class, 'index'])->name('offices.index');
        Route::get('/offices/create', [\App\Http\Controllers\Admin\GovernmentOfficeController::class, 'create'])->name('offices.create');
        Route::post('/offices', [\App\Http\Controllers\Admin\GovernmentOfficeController::class, 'store'])->name('offices.store');
        Route::get('/offices/{office}', [\App\Http\Controllers\Admin\GovernmentOfficeController::class, 'show'])->name('offices.show');
        Route::get('/offices/{office}/edit', [\App\Http\Controllers\Admin\GovernmentOfficeController::class, 'edit'])->name('offices.edit');
        Route::match(['put', 'patch'], '/offices/{office}', [\App\Http\Controllers\Admin\GovernmentOfficeController::class, 'update'])->name('offices.update');
        Route::delete('/offices/{office}', [\App\Http\Controllers\Admin\GovernmentOfficeController::class, 'destroy'])->name('offices.destroy');

        Route::get('/municipality-users', [AdminController::class, 'municipalityUsers'])->name('municipality.users');
        Route::post('/municipality-users', [AdminController::class, 'storeMunicipalityUser'])->name('municipality.users.store');
        Route::patch('/municipality-users/{user}/toggle-status', [AdminController::class, 'toggleMunicipalityUserStatus'])->name('municipality.users.toggle-status');
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
