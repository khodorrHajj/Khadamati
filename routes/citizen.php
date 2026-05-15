<?php

use App\Http\Controllers\Citizen\AppointmentController;
use App\Http\Controllers\Citizen\CryptoPaymentController;
use App\Http\Controllers\Citizen\DashboardController;
use App\Http\Controllers\Citizen\FeedbackController;
use App\Http\Controllers\Citizen\NotificationController;
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
        Route::get('/services/{service}/request', [ServiceCatalogController::class, 'createRequest'])->name('services.request.create');
        Route::post('/services/{service}/request', [ServiceCatalogController::class, 'storeRequest'])->name('services.request.store');
        Route::get('/services/{service}', [ServiceCatalogController::class, 'service'])->name('services.show');
        Route::post('/services/{service}/requests', [ServiceCatalogController::class, 'storeRequest'])->name('requests.store');
        Route::get('/feedback', [FeedbackController::class, 'index'])->name('feedback.index');
        Route::get('/requests', [ServiceCatalogController::class, 'requests'])->name('requests.index');
        Route::get('/requests/{serviceRequest}/documents/{requestDocument}/download', [ServiceCatalogController::class, 'downloadDocument'])->name('requests.documents.download');
        Route::get('/requests/{serviceRequest}/receipt/download', [ServiceCatalogController::class, 'downloadReceipt'])->name('requests.receipt.download');
        Route::get('/requests/{serviceRequest}/official-response/download', [ServiceCatalogController::class, 'downloadOfficialResponse'])->name('requests.official-response.download');
        Route::get('/requests/{serviceRequest}', [ServiceCatalogController::class, 'request'])->name('requests.show');
        Route::post('/requests/{serviceRequest}/documents', [ServiceCatalogController::class, 'storeDocument'])->name('requests.documents.store');
        Route::post('/requests/{serviceRequest}/feedback', [FeedbackController::class, 'store'])->name('requests.feedback.store');
        Route::get('/messages', [RequestMessageController::class, 'index'])->name('messages.index');
        Route::get('/messages/unread-count', [RequestMessageController::class, 'unreadCount'])->name('messages.unread-count');
        Route::get('/messages/{serviceRequest}', [RequestMessageController::class, 'show'])->name('messages.show');
        Route::post('/requests/{serviceRequest}/messages', [RequestMessageController::class, 'store'])->name('requests.messages.store');
        Route::post('/requests/{serviceRequest}/appointments', [AppointmentController::class, 'store'])->name('requests.appointments.store');
        // Crypto Payments
        Route::get('/services/{service}/pay',  [CryptoPaymentController::class, 'show'])->name('payment.show');
        Route::post('/services/{service}/pay', [CryptoPaymentController::class, 'create'])->name('payment.create');
        Route::get('/payment/success',         [CryptoPaymentController::class, 'success'])->name('payment.success');
        Route::get('/payment/cancelled',       [CryptoPaymentController::class, 'cancelled'])->name('payment.cancelled');

        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
        Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    });
