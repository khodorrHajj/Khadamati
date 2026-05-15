<?php

use App\Http\Controllers\Admin\AppointmentController;
use App\Http\Controllers\Admin\CitizenAccountController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FeedbackController;
use App\Http\Controllers\Admin\GovernmentOfficeController;
use App\Http\Controllers\Admin\MunicipalityController;
use App\Http\Controllers\Admin\MunicipalityUserController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\RequestController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ServiceCategoryController;
use App\Http\Controllers\Admin\ServiceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['checkIfConnected', 'checkRole:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        Route::post('municipalities/validate-step', [MunicipalityController::class, 'validateStep'])->name('municipalities.validate-step');
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
        Route::delete('/citizens/{citizen}', [CitizenAccountController::class, 'destroy'])->name('citizens.destroy');
        Route::patch('/citizens/{citizen}/activate', [CitizenAccountController::class, 'activate'])->name('citizens.activate');
        Route::patch('/citizens/{citizen}/deactivate', [CitizenAccountController::class, 'deactivate'])->name('citizens.deactivate');

        Route::get('/requests', [RequestController::class, 'index'])->name('requests.index');
        Route::get('/requests/poll', [RequestController::class, 'poll'])->name('requests.poll');
        Route::get('/requests/{serviceRequest}', [RequestController::class, 'show'])->name('requests.show');
        Route::get('/requests/{serviceRequest}/documents/{requestDocument}/download', [RequestController::class, 'downloadDocument'])->name('requests.documents.download');
        Route::get('/requests/{serviceRequest}/receipt/download', [RequestController::class, 'downloadReceipt'])->name('requests.receipt.download');
        Route::get('/requests/{serviceRequest}/official-response/download', [RequestController::class, 'downloadOfficialResponse'])->name('requests.official-response.download');
        Route::match(['put', 'patch'], '/requests/{serviceRequest}', [RequestController::class, 'update'])->name('requests.update');
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
        Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.export.pdf');
        Route::get('/reports/export/csv', [ReportController::class, 'exportCsv'])->name('reports.export.csv');

        // Services Overview
        Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
        Route::get('/services/{service}', [ServiceController::class, 'show'])->name('services.show');
        Route::patch('/services/{service}/toggle-status', [ServiceController::class, 'toggleStatus'])->name('services.toggle-status');

        // Feedback Management
        Route::get('/feedback', [FeedbackController::class, 'index'])->name('feedback.index');
        Route::get('/feedback/{feedback}', [FeedbackController::class, 'show'])->name('feedback.show');
        Route::post('/feedback/{feedback}/respond', [FeedbackController::class, 'respond'])->name('feedback.respond');

        // Appointments Overview
        Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
        Route::get('/appointments/{appointment}', [AppointmentController::class, 'show'])->name('appointments.show');

        // Service Categories Management
        Route::get('/categories', [ServiceCategoryController::class, 'index'])->name('categories.index');
        Route::get('/categories/create', [ServiceCategoryController::class, 'create'])->name('categories.create');
        Route::post('/categories', [ServiceCategoryController::class, 'store'])->name('categories.store');
        Route::get('/categories/{category}', [ServiceCategoryController::class, 'show'])->name('categories.show');
        Route::get('/categories/{category}/edit', [ServiceCategoryController::class, 'edit'])->name('categories.edit');
        Route::match(['put', 'patch'], '/categories/{category}', [ServiceCategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [ServiceCategoryController::class, 'destroy'])->name('categories.destroy');
    });
