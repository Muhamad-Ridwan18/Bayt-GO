<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OtpController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/otp/send', [OtpController::class, 'send']);
Route::post('/otp/verify', [OtpController::class, 'verify']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/customer/dashboard', [\App\Http\Controllers\Api\Customer\DashboardController::class, 'index']);
    Route::get('/muthowif/dashboard', [\App\Http\Controllers\Api\Muthowif\DashboardController::class, 'index']);
    Route::get('/muthowif/services', [\App\Http\Controllers\Api\Muthowif\MuthowifServiceController::class, 'index']);
    Route::put('/muthowif/services/{id}', [\App\Http\Controllers\Api\Muthowif\MuthowifServiceController::class, 'update']);
    Route::get('/muthowif/blocked-dates', [\App\Http\Controllers\Api\Muthowif\MuthowifBlockedDateController::class, 'index']);
    Route::post('/muthowif/blocked-dates', [\App\Http\Controllers\Api\Muthowif\MuthowifBlockedDateController::class, 'store']);
    Route::delete('/muthowif/blocked-dates/{id}', [\App\Http\Controllers\Api\Muthowif\MuthowifBlockedDateController::class, 'destroy']);
    Route::get('/muthowif/bookings', [\App\Http\Controllers\Api\Muthowif\MuthowifBookingController::class, 'index']);
    Route::get('/muthowif/bookings/{id}', [\App\Http\Controllers\Api\Muthowif\MuthowifBookingController::class, 'show']);
    Route::post('/muthowif/bookings/{id}/confirm', [\App\Http\Controllers\Api\Muthowif\MuthowifBookingController::class, 'confirm']);
    Route::post('/muthowif/bookings/{id}/cancel', [\App\Http\Controllers\Api\Muthowif\MuthowifBookingController::class, 'cancel']);
    
    Route::get('/muthowif/wallet', [\App\Http\Controllers\Api\Muthowif\WalletController::class, 'index']);
    Route::post('/muthowif/withdrawals', [\App\Http\Controllers\Api\Muthowif\WalletController::class, 'storeWithdrawal']);

    Route::post('/logout', [AuthController::class, 'logout']);

    // Chat conversations list
    Route::get('/chat/conversations', [\App\Http\Controllers\Api\GlobalChatApiController::class, 'conversations']);

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
