<?php

use App\Http\Controllers\Citizen\AppointmentController;
use App\Http\Controllers\Citizen\StripePaymentController;
use App\Http\Controllers\Citizen\DashboardController;
use App\Http\Controllers\Citizen\FeedbackController;
use App\Http\Controllers\Citizen\NotificationController;
use App\Http\Controllers\Citizen\PaymentHistoryController;
use App\Http\Controllers\Citizen\ProfileController;
use App\Http\Controllers\Citizen\RequestMessageController;
use App\Http\Controllers\Citizen\ServiceCatalogController;
use Illuminate\Support\Facades\Route;

Route::middleware(['checkIfConnected', 'checkRole:citizen'])
    ->prefix('citizen')
    ->name('citizen.')
    ->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::post('/profile/password/send-otp', [ProfileController::class, 'sendPasswordOtp'])->name('profile.password.send-otp');
        Route::post('/profile/password/confirm-otp', [ProfileController::class, 'confirmPasswordOtp'])->name('profile.password.confirm-otp');
        Route::post('/profile/password/resend-otp', [ProfileController::class, 'resendPasswordOtp'])->name('profile.password.resend-otp');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
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
        Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
        Route::post('/requests/{serviceRequest}/appointments', [AppointmentController::class, 'store'])->name('requests.appointments.store');
        // Stripe Payments
        Route::get('/services/{service}/pay',  [StripePaymentController::class, 'show'])->name('payment.show');
        Route::post('/services/{service}/pay', [StripePaymentController::class, 'create'])->name('payment.create');
        Route::get('/payment/success',         [StripePaymentController::class, 'success'])->name('payment.success');
        Route::get('/payment/cancelled',       [StripePaymentController::class, 'cancelled'])->name('payment.cancelled');
        Route::get('/payment/history',         [PaymentHistoryController::class, 'index'])->name('payment.history');

        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
        Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    });
