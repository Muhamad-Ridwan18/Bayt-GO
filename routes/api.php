<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OtpController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/otp/send', [OtpController::class, 'send']);
Route::post('/otp/verify', [OtpController::class, 'verify']);

Route::get('/home', [\App\Http\Controllers\Api\HomeApiController::class, 'index']);

Route::get('/directory', [\App\Http\Controllers\Api\MuthowifDirectoryApiController::class, 'index']);
Route::get('/directory/{id}', [\App\Http\Controllers\Api\MuthowifDirectoryApiController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/customer/dashboard', [\App\Http\Controllers\Api\Customer\DashboardController::class, 'index']);
    Route::get('/muthowif/dashboard', [\App\Http\Controllers\Api\Muthowif\DashboardController::class, 'index']);

    // Customer Bookings
    Route::get('/customer/bookings', [\App\Http\Controllers\Api\Customer\BookingApiController::class, 'index']);
    Route::get('/customer/bookings/{booking}', [\App\Http\Controllers\Api\Customer\BookingApiController::class, 'show']);
    Route::post('/customer/bookings', [\App\Http\Controllers\Api\Customer\BookingApiController::class, 'store']);
    Route::post('/customer/bookings/{booking}/pay', [\App\Http\Controllers\Api\Customer\BookingApiController::class, 'pay']);
    Route::get('/customer/bookings/{booking}/invoice', [\App\Http\Controllers\Api\Customer\BookingApiController::class, 'invoice']);
    Route::post('/customer/bookings/{booking}/review', [\App\Http\Controllers\Api\Customer\BookingApiController::class, 'review']);
    Route::post('/customer/bookings/{booking}/refund-request', [\App\Http\Controllers\Api\Customer\BookingApiController::class, 'storeRefundRequest']);
    Route::post('/customer/bookings/{booking}/reschedule-request', [\App\Http\Controllers\Api\Customer\BookingApiController::class, 'storeRescheduleRequest']);
    Route::post('/customer/bookings/{booking}/complete', [\App\Http\Controllers\Api\Customer\BookingApiController::class, 'complete']);
    Route::post('/customer/bookings/{booking}/cancel', [\App\Http\Controllers\Api\Customer\BookingApiController::class, 'cancel']);
    Route::post('/customer/bookings/{booking}/emergency-report', [\App\Http\Controllers\Api\Customer\BookingEmergencyApiController::class, 'store']);
    Route::post('/customer/bookings/{booking}/emergency-select/{offer}', [\App\Http\Controllers\Api\Customer\BookingEmergencyApiController::class, 'selectReplacement']);

    Route::get('/realtime/config', [\App\Http\Controllers\Api\RealtimeConfigApiController::class, 'show']);

    Route::get('/muthowif/services', [\App\Http\Controllers\Api\Muthowif\MuthowifServiceController::class, 'index']);
    Route::put('/muthowif/services/{id}', [\App\Http\Controllers\Api\Muthowif\MuthowifServiceController::class, 'update']);
    Route::get('/muthowif/blocked-dates', [\App\Http\Controllers\Api\Muthowif\MuthowifBlockedDateController::class, 'index']);
    Route::post('/muthowif/blocked-dates', [\App\Http\Controllers\Api\Muthowif\MuthowifBlockedDateController::class, 'store']);
    Route::delete('/muthowif/blocked-dates/{id}', [\App\Http\Controllers\Api\Muthowif\MuthowifBlockedDateController::class, 'destroy']);
    Route::get('/muthowif/jadwal', [\App\Http\Controllers\Api\Muthowif\MuthowifBlockedDateController::class, 'index']);
    Route::post('/muthowif/jadwal', [\App\Http\Controllers\Api\Muthowif\MuthowifBlockedDateController::class, 'store']);
    Route::delete('/muthowif/jadwal/{id}', [\App\Http\Controllers\Api\Muthowif\MuthowifBlockedDateController::class, 'destroy']);
    Route::get('/muthowif/bookings', [\App\Http\Controllers\Api\Muthowif\MuthowifBookingController::class, 'index']);
    Route::get('/muthowif/bookings/{id}', [\App\Http\Controllers\Api\Muthowif\MuthowifBookingController::class, 'show']);
    Route::post('/muthowif/bookings/{id}/confirm', [\App\Http\Controllers\Api\Muthowif\MuthowifBookingController::class, 'confirm']);
    Route::post('/muthowif/bookings/{id}/cancel', [\App\Http\Controllers\Api\Muthowif\MuthowifBookingController::class, 'cancel']);
    Route::post('/muthowif/bookings/{booking}/reschedule-requests/{rescheduleRequest}/approve', [\App\Http\Controllers\Api\Muthowif\MuthowifBookingController::class, 'approveReschedule']);
    Route::post('/muthowif/bookings/{booking}/reschedule-requests/{rescheduleRequest}/reject', [\App\Http\Controllers\Api\Muthowif\MuthowifBookingController::class, 'rejectReschedule']);
    
    Route::get('/muthowif/wallet', [\App\Http\Controllers\Api\Muthowif\WalletController::class, 'index']);
    Route::post('/muthowif/withdrawals', [\App\Http\Controllers\Api\Muthowif\WalletController::class, 'storeWithdrawal']);

    Route::post('/logout', [AuthController::class, 'logout']);

    // Chat conversations list
    Route::get('/chat/conversations', [\App\Http\Controllers\Api\GlobalChatApiController::class, 'conversations']);

    Route::post('/push-tokens', [\App\Http\Controllers\Api\DevicePushTokenApiController::class, 'store']);
    Route::delete('/push-tokens', [\App\Http\Controllers\Api\DevicePushTokenApiController::class, 'destroy']);

    // Reverb private channel authentication (for mobile WebSocket)
    Route::post('/broadcasting/auth', [\Illuminate\Broadcasting\BroadcastController::class, 'authenticate']);

    // Chat (booking-scoped, polling-based)
    Route::get('/bookings/{booking}/chat', [\App\Http\Controllers\Api\BookingChatApiController::class, 'index'])->name('api.chat.messages');
    Route::post('/bookings/{booking}/chat', [\App\Http\Controllers\Api\BookingChatApiController::class, 'store']);
    Route::get('/bookings/{booking}/chat/image/{message}', [\App\Http\Controllers\Api\BookingChatApiController::class, 'image'])->name('api.chat.image');

    Route::get('/profile', [\App\Http\Controllers\Api\ProfileController::class, 'show']);
    Route::patch('/profile', [\App\Http\Controllers\Api\ProfileController::class, 'update']);
    Route::patch('/profile/public', [\App\Http\Controllers\Api\ProfileController::class, 'updatePublic']);
    Route::post('/profile/photo', [\App\Http\Controllers\Api\ProfileController::class, 'uploadPhoto']);
    Route::post('/profile/ktp', [\App\Http\Controllers\Api\ProfileController::class, 'uploadKtp']);
    Route::post('/profile/documents', [\App\Http\Controllers\Api\ProfileController::class, 'uploadSupportingDocument']);
    Route::delete('/profile/documents/{id}', [\App\Http\Controllers\Api\ProfileController::class, 'deleteSupportingDocument']);
    Route::put('/profile/password', [\App\Http\Controllers\Api\ProfileController::class, 'updatePassword']);
});

Route::get('/test', function () {
    return response()->json(['message' => 'API is working!']);
});
