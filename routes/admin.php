<?php

use App\Http\Controllers\Admin\CitizenAccountController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GovernmentOfficeController;
use App\Http\Controllers\Admin\IdentityVerificationController;
use App\Http\Controllers\Admin\MunicipalityController;
use App\Http\Controllers\Admin\MunicipalityUserController;
use App\Http\Controllers\Admin\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['checkIfConnected', 'checkRole:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        Route::resource('municipalities', MunicipalityController::class)->names([
            'index' => 'municipalities.index',
            'create' => 'municipalities.create',
            'store' => 'municipalities.store',
            'show' => 'municipalities.show',
            'edit' => 'municipalities.edit',
            'update' => 'municipalities.update',
            'destroy' => 'municipalities.destroy',
        ]);

        Route::get('/offices', [GovernmentOfficeController::class, 'index'])->name('offices.index');
        Route::get('/offices/create', [GovernmentOfficeController::class, 'create'])->name('offices.create');
        Route::post('/offices', [GovernmentOfficeController::class, 'store'])->name('offices.store');
        Route::get('/offices/{office}', [GovernmentOfficeController::class, 'show'])->name('offices.show');
        Route::get('/offices/{office}/edit', [GovernmentOfficeController::class, 'edit'])->name('offices.edit');
        Route::match(['put', 'patch'], '/offices/{office}', [GovernmentOfficeController::class, 'update'])->name('offices.update');
        Route::delete('/offices/{office}', [GovernmentOfficeController::class, 'destroy'])->name('offices.destroy');

        Route::get('/municipality-users', [MunicipalityUserController::class, 'index'])->name('municipality.users');
        Route::post('/municipality-users', [MunicipalityUserController::class, 'store'])->name('municipality.users.store');
        Route::patch('/municipality-users/{user}/toggle-status', [MunicipalityUserController::class, 'toggleStatus'])->name('municipality.users.toggle-status');

        Route::get('/citizens', [CitizenAccountController::class, 'index'])->name('citizens.index');
        Route::get('/citizens/{citizen}', [CitizenAccountController::class, 'show'])->name('citizens.show');
        Route::patch('/citizens/{citizen}/activate', [CitizenAccountController::class, 'activate'])->name('citizens.activate');
        Route::patch('/citizens/{citizen}/deactivate', [CitizenAccountController::class, 'deactivate'])->name('citizens.deactivate');

        Route::get('/identity-verifications', [IdentityVerificationController::class, 'index'])->name('identity-verifications.index');
        Route::get('/identity-verifications/{verification}', [IdentityVerificationController::class, 'show'])->name('identity-verifications.show');
        Route::patch('/identity-verifications/{verification}/approve', [IdentityVerificationController::class, 'approve'])->name('identity-verifications.approve');
        Route::patch('/identity-verifications/{verification}/reject', [IdentityVerificationController::class, 'reject'])->name('identity-verifications.reject');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    });
