<?php

use App\Enums\MuthowifVerificationStatus;
use App\Http\Controllers\Admin\BookingRefundController;
use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\Admin\LogsController;
use App\Http\Controllers\Admin\MuthowifVerificationController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\WithdrawalsController;
use App\Http\Controllers\BookingChatController;
use App\Http\Controllers\Customer\BookingController as CustomerBookingController;
use App\Http\Controllers\GlobalChatController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Muthowif\BookingController as MuthowifBookingController;
use App\Http\Controllers\Muthowif\MuthowifDashboardCalendarController;
use App\Http\Controllers\Muthowif\MuthowifScheduleController;
use App\Http\Controllers\Muthowif\MuthowifServiceController;
use App\Http\Controllers\Muthowif\WithdrawController as MuthowifWithdrawController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\MuthowifDirectoryController;
use App\Http\Middleware\EnsureUserRole;
use App\Models\MuthowifBlockedDate;
use App\Models\MuthowifProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::bind('publicProfile', function (string $value) {
    return MuthowifProfile::query()
        ->whereKey($value)
        ->where('verification_status', MuthowifVerificationStatus::Approved)
        ->firstOrFail();
});

Route::bind('blockedDate', function (string $value) {
    $row = MuthowifBlockedDate::query()->whereKey($value)->firstOrFail();
    $user = auth()->user();
    if (! $user?->muthowifProfile || $row->muthowif_profile_id !== $user->muthowifProfile->id) {
        abort(403);
    }

    return $row;
});

Route::post('/payments/midtrans/notification', [PaymentWebhookController::class, 'handle'])
    ->name('payments.midtrans.notification');

Route::get('/layanan', [MuthowifDirectoryController::class, 'index'])->name('layanan.index');
Route::get('/layanan/{publicProfile}/foto', [MuthowifDirectoryController::class, 'photo'])->name('layanan.photo');
Route::get('/layanan/{publicProfile}', [MuthowifDirectoryController::class, 'show'])->name('layanan.show');

Route::get('/locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

Route::get('/', function () {
    return view('welcome');
});

Route::get('/muthowif/daftar/menunggu', function () {
    return view('auth.muthowif-registration-pending');
})->name('muthowif.registration.pending');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

Route::get('/dashboard/muthowif-calendar', MuthowifDashboardCalendarController::class)
    ->middleware(['auth'])
    ->name('dashboard.muthowif-calendar');

Route::get('/logs', [LogsController::class, 'index'])
    ->middleware(['auth', EnsureUserRole::class.':admin'])
    ->name('admin.logs.index');

Route::post('/logs/clear', [LogsController::class, 'clear'])
    ->middleware(['auth', EnsureUserRole::class.':admin'])
    ->name('admin.logs.clear');

Route::middleware('guest')->get('/masuk/setelah', function (Request $request) {
    $next = $request->query('next');
    if (is_string($next) && str_starts_with($next, '/') && ! str_starts_with($next, '//') && strlen($next) < 2048) {
        $base = rtrim((string) config('app.url'), '/');
        session(['url.intended' => $base.$next]);
    }

    return redirect()->route('login');
})->name('login.intended');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/public', [ProfileController::class, 'updatePublicProfile'])->name('profile.public.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/chat/conversations', [GlobalChatController::class, 'index'])->name('chat.conversations');

    Route::middleware([EnsureUserRole::class.':customer'])->prefix('bookings')->name('bookings.')->group(function () {
        Route::get('/', [CustomerBookingController::class, 'index'])->name('index');
        Route::post('/', [CustomerBookingController::class, 'store'])->name('store');
        Route::get('{booking}/pembayaran', [CustomerBookingController::class, 'payment'])->name('payment');
        Route::get('{booking}/payment-status', [CustomerBookingController::class, 'paymentStatus'])->name('payment.status');
        Route::get('{booking}/invoice', [CustomerBookingController::class, 'invoice'])->name('invoice');
        Route::post('{booking}/selesaikan', [CustomerBookingController::class, 'complete'])->name('complete');
        Route::post('{booking}/review', [CustomerBookingController::class, 'review'])->name('review');
        Route::post('{booking}/refund-request', [CustomerBookingController::class, 'storeRefundRequest'])->name('refund_request.store');
        Route::post('{booking}/reschedule-request', [CustomerBookingController::class, 'storeRescheduleRequest'])->name('reschedule_request.store');
        Route::get('{booking}/chat/messages', [BookingChatController::class, 'index'])->name('chat.messages');
        Route::get('{booking}/chat/unread-count', [BookingChatController::class, 'unreadCount'])->name('chat.unread-count');
        Route::post('{booking}/chat/messages', [BookingChatController::class, 'store'])->name('chat.messages.store');
        Route::get('{booking}/chat/messages/{message}/image', [BookingChatController::class, 'image'])->name('chat.messages.image');
        Route::get('{booking}/documents/{type}', [CustomerBookingController::class, 'downloadDocument'])
            ->where('type', 'outbound|return|passport|itinerary|visa')
            ->name('documents.show');
        Route::get('{booking}', [CustomerBookingController::class, 'show'])->name('show');
        Route::post('{booking}/cancel', [CustomerBookingController::class, 'cancel'])->name('cancel');
    });

    Route::middleware([EnsureUserRole::class.':muthowif', 'verified.muthowif'])
        ->prefix('muthowif')
        ->name('muthowif.')
        ->group(function () {
            Route::get('pelayanan', [MuthowifServiceController::class, 'edit'])->name('pelayanan.edit');
            Route::put('pelayanan/group', [MuthowifServiceController::class, 'updateGroup'])->name('pelayanan.group');
            Route::put('pelayanan/private', [MuthowifServiceController::class, 'updatePrivate'])->name('pelayanan.private');

            Route::get('jadwal', [MuthowifScheduleController::class, 'index'])->name('jadwal.index');
            Route::post('jadwal', [MuthowifScheduleController::class, 'store'])->name('jadwal.store');
            Route::delete('jadwal/{blockedDate}', [MuthowifScheduleController::class, 'destroy'])->name('jadwal.destroy');

            Route::get('bookings', [MuthowifBookingController::class, 'index'])->name('bookings.index');
            Route::get('bookings/{booking}/chat/messages', [BookingChatController::class, 'index'])->name('bookings.chat.messages');
            Route::get('bookings/{booking}/chat/unread-count', [BookingChatController::class, 'unreadCount'])->name('bookings.chat.unread-count');
            Route::post('bookings/{booking}/chat/messages', [BookingChatController::class, 'store'])->name('bookings.chat.messages.store');
            Route::get('bookings/{booking}/chat/messages/{message}/image', [BookingChatController::class, 'image'])->name('bookings.chat.messages.image');
            Route::get('bookings/{booking}/documents/{type}', [CustomerBookingController::class, 'downloadDocument'])
                ->where('type', 'outbound|return|passport|itinerary|visa')
                ->name('bookings.documents.show');
            Route::get('bookings/{booking}', [MuthowifBookingController::class, 'show'])->name('bookings.show');
            Route::post('bookings/{booking}/confirm', [MuthowifBookingController::class, 'confirm'])->name('bookings.confirm');
            Route::post('bookings/{booking}/cancel', [MuthowifBookingController::class, 'cancel'])->name('bookings.cancel');
            Route::post('bookings/{booking}/reschedule-requests/{rescheduleRequest}/approve', [MuthowifBookingController::class, 'approveReschedule'])->name('bookings.reschedule_requests.approve');
            Route::post('bookings/{booking}/reschedule-requests/{rescheduleRequest}/reject', [MuthowifBookingController::class, 'rejectReschedule'])->name('bookings.reschedule_requests.reject');

            Route::get('withdrawals', [MuthowifWithdrawController::class, 'index'])->name('withdrawals.index');
            Route::post('withdrawals', [MuthowifWithdrawController::class, 'store'])->name('withdrawals.store');
        });

    Route::middleware([EnsureUserRole::class.':admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('pengguna', [UserManagementController::class, 'index'])->name('users.index');
        Route::get('pengguna/{user}/ubah', [UserManagementController::class, 'edit'])->name('users.edit');
        Route::patch('pengguna/{user}', [UserManagementController::class, 'update'])->name('users.update');
        Route::get('refund-menunggu', [BookingRefundController::class, 'index'])->name('refunds.index');
        Route::post('refund-menunggu/{refund}/selesai', [BookingRefundController::class, 'complete'])->name('refunds.complete');
        Route::get('keuangan', [FinanceController::class, 'index'])->name('finance.index');
        Route::get('withdrawals', [WithdrawalsController::class, 'index'])->name('withdrawals.index');
        Route::post('withdrawals/{withdrawal}/approve', [WithdrawalsController::class, 'approve'])->name('withdrawals.approve');
        Route::post('withdrawals/{withdrawal}/selesai-transfer', [WithdrawalsController::class, 'markTransferred'])->name('withdrawals.mark_transferred');
        Route::post('withdrawals/{withdrawal}/gagal-transfer', [WithdrawalsController::class, 'markTransferFailed'])->name('withdrawals.mark_transfer_failed');
        Route::get('muthowif', [MuthowifVerificationController::class, 'index'])->name('muthowif.index');
        Route::get('muthowif/{profile}/photo', [MuthowifVerificationController::class, 'photo'])->name('muthowif.photo');
        Route::get('muthowif/{profile}/ktp', [MuthowifVerificationController::class, 'ktp'])->name('muthowif.ktp');
        Route::get('muthowif/{profile}/documents/{document}', [MuthowifVerificationController::class, 'supportingDocument'])->name('muthowif.document');
        Route::post('muthowif/{profile}/approve', [MuthowifVerificationController::class, 'approve'])->name('muthowif.approve');
        Route::post('muthowif/{profile}/reject', [MuthowifVerificationController::class, 'reject'])->name('muthowif.reject');
        Route::get('muthowif/{profile}', [MuthowifVerificationController::class, 'show'])->name('muthowif.show');
    });
});

require __DIR__.'/auth.php';
