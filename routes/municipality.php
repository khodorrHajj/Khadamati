<?php

use App\Http\Controllers\Municipality\AppointmentController;
use App\Http\Controllers\Municipality\DashboardController;
use App\Http\Controllers\Municipality\FeedbackController;
use App\Http\Controllers\Municipality\NotificationController;
use App\Http\Controllers\Municipality\OfficeProfileController;
use App\Http\Controllers\Municipality\PaymentController;
use App\Http\Controllers\Municipality\ReportController;
use App\Http\Controllers\Municipality\RequestController;
use App\Http\Controllers\Municipality\RequestMessageController;
use App\Http\Controllers\Municipality\ServiceCategoryController;
use App\Http\Controllers\Municipality\ServiceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['checkIfConnected', 'checkRole:municipality'])
    ->prefix('municipality')
    ->name('municipality.')
    ->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        Route::get('/office', [OfficeProfileController::class, 'show'])->name('office.show');
        Route::get('/office/edit', [OfficeProfileController::class, 'edit'])->name('office.edit');
        Route::match(['put', 'patch'], '/office', [OfficeProfileController::class, 'update'])->name('office.update');

        Route::get('/categories', [ServiceCategoryController::class, 'index'])->name('categories');
        Route::post('/categories', [ServiceCategoryController::class, 'store'])->name('categories.store');
        Route::get('/categories/{category}/edit', [ServiceCategoryController::class, 'edit'])->name('categories.edit');
        Route::match(['put', 'patch'], '/categories/{category}', [ServiceCategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [ServiceCategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('/services', [ServiceController::class, 'index'])->name('services');
        Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
        Route::get('/services/{service}/edit', [ServiceController::class, 'edit'])->name('services.edit');
        Route::match(['put', 'patch'], '/services/{service}', [ServiceController::class, 'update'])->name('services.update');
        Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');
        Route::patch('/services/{service}/toggle-status', [ServiceController::class, 'toggleStatus'])->name('services.toggle-status');

        Route::get('/requests', [RequestController::class, 'index'])->name('requests.index');
        Route::get('/requests/{serviceRequest}', [RequestController::class, 'show'])->name('requests.show');
        Route::get('/requests/{serviceRequest}/documents/{requestDocument}/download', [RequestController::class, 'downloadDocument'])->name('requests.documents.download');
        Route::get('/requests/{serviceRequest}/receipt/download', [RequestController::class, 'downloadReceipt'])->name('requests.receipt.download');
        Route::get('/requests/{serviceRequest}/official-response/download', [RequestController::class, 'downloadOfficialResponse'])->name('requests.official-response.download');
        Route::match(['put', 'patch'], '/requests/{serviceRequest}', [RequestController::class, 'update'])->name('requests.update');
        Route::post('/requests/{serviceRequest}/messages', [RequestMessageController::class, 'store'])->name('requests.messages.store');

        Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
        Route::post('/appointments/slots', [AppointmentController::class, 'storeSlot'])->name('appointments.slots.store');
        Route::patch('/appointments/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update');

        Route::get('/feedback', [FeedbackController::class, 'index'])->name('feedback.index');
        Route::patch('/feedback/{feedback}', [FeedbackController::class, 'update'])->name('feedback.update');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('/messages/open', [RequestMessageController::class, 'open'])->name('messages.open');
        Route::get('/messages', [RequestMessageController::class, 'index'])->name('messages.index');
        Route::get('/messages/unread-count', [RequestMessageController::class, 'unreadCount'])->name('messages.unread-count');
        Route::get('/messages/{serviceRequest}', [RequestMessageController::class, 'show'])->name('messages.show');
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
        Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    });
