<?php

use App\Http\Controllers\Citizen\AppointmentController;
use App\Http\Controllers\Citizen\DashboardController;
use App\Http\Controllers\Citizen\FeedbackController;
use App\Http\Controllers\Citizen\RequestMessageController;
use App\Http\Controllers\Citizen\ServiceCatalogController;
use Illuminate\Support\Facades\Route;

Route::middleware(['checkIfConnected', 'checkRole:citizen'])
    ->prefix('citizen')
    ->name('citizen.')
    ->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/services', [ServiceCatalogController::class, 'services'])->name('services.index');
        Route::get('/offices', [ServiceCatalogController::class, 'offices'])->name('offices.index');
        Route::get('/offices/{office}', [ServiceCatalogController::class, 'office'])->name('offices.show');
        Route::get('/offices/{office}/categories/{category}', [ServiceCatalogController::class, 'category'])->name('categories.show');
        Route::get('/services/{service}', [ServiceCatalogController::class, 'service'])->name('services.show');
        Route::post('/services/{service}/requests', [ServiceCatalogController::class, 'storeRequest'])->name('requests.store');
        Route::get('/requests/{serviceRequest}', [ServiceCatalogController::class, 'request'])->name('requests.show');
        Route::post('/requests/{serviceRequest}/documents', [ServiceCatalogController::class, 'storeDocument'])->name('requests.documents.store');
        Route::post('/requests/{serviceRequest}/feedback', [FeedbackController::class, 'store'])->name('requests.feedback.store');
        Route::post('/requests/{serviceRequest}/messages', [RequestMessageController::class, 'store'])->name('requests.messages.store');
        Route::post('/requests/{serviceRequest}/appointments', [AppointmentController::class, 'store'])->name('requests.appointments.store');
    });
