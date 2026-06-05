<?php

use App\Enums\MuthowifVerificationStatus;
use App\Http\Controllers\Admin\AdminSettingsHubController;
use App\Http\Controllers\Admin\ArticlesAdminController;
use App\Http\Controllers\Admin\BookingEmergencyController;
use App\Http\Controllers\Admin\BookingRefundController;
use App\Http\Controllers\Admin\CampaignsAdminController;
use App\Http\Controllers\Admin\CompanyApprovalController;
use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\Admin\MootaWebhookHistoriesLiveController;
use App\Http\Controllers\Admin\MuthowifVerificationController;
use App\Http\Controllers\Admin\ServiceMonitorController;
use App\Http\Controllers\Admin\SiteAppearanceController;
use App\Http\Controllers\Admin\SupportTicketsController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\WhatsAppBroadcastController;
use App\Http\Controllers\Admin\WithdrawalsController;
use App\Http\Controllers\BookingChatController;
use App\Http\Controllers\Customer\BookingController as CustomerBookingController;
use App\Http\Controllers\Customer\BookingEmergencyController as CustomerBookingEmergencyController;
use App\Http\Controllers\GlobalChatController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MootaWebhookController;
use App\Http\Controllers\Muthowif\BookingController as MuthowifBookingController;
use App\Http\Controllers\Muthowif\EmergencyOfferController;
use App\Http\Controllers\Muthowif\MuthowifDashboardCalendarController;
use App\Http\Controllers\Muthowif\MuthowifPortfolioController;
use App\Http\Controllers\Muthowif\MuthowifScheduleController;
use App\Http\Controllers\Muthowif\MuthowifServiceController;
use App\Http\Controllers\Muthowif\WithdrawController as MuthowifWithdrawController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\ArticleController;
use App\Http\Controllers\Public\CampaignController;
use App\Http\Controllers\Public\MuthowifDirectoryController;
use App\Http\Controllers\Public\SeoLandingController;
use App\Http\Controllers\Public\WelcomeController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\TermsController;
use App\Http\Middleware\EnsureUserRole;
use App\Models\Campaign;
use App\Models\MuthowifBlockedDate;
use App\Models\MuthowifProfile;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::bind('publicProfile', function (string $value) {
    $query = MuthowifProfile::query()
        ->where('verification_status', MuthowifVerificationStatus::Approved);

    if (Str::of($value)->isUuid()) {
        $query->whereKey($value);
    } else {
        $query->where('slug', $value);
    }

    return $query->firstOrFail();
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

Route::post('/payments/doku/notification', [PaymentWebhookController::class, 'doku'])
    ->name('payments.doku.notification');

/**
 * Endpoint uji webhook: POST dari gateway / layanan luar (tanpa CSRF).
 * Pakai URL: {APP_URL}/webhooks/test
 */
Route::withoutMiddleware([ValidateCsrfToken::class])
    ->post('/webhooks/test', function (Request $request) {
        Log::info('webhooks.test', [
            'ip' => $request->ip(),
            'content_type' => $request->header('Content-Type'),
            'raw_preview' => Str::limit($request->getContent(), 8192),
        ]);

        return response()->json([
            'ok' => true,
            'received_at' => now()->toIso8601String(),
            'hint' => 'Uji webhook; produksi pakai POST /payments/doku/notification atau /payments/midtrans/notification.',
        ]);
    })->name('webhooks.test');

Route::get('/docs/moota-webhook', function () {
    return view('docs.moota-webhook', [
        'webhookUrl' => route('webhooks.moota', absolute: true),
    ]);
})->name('docs.moota_webhook');

Route::middleware(['moota.ip'])
    ->post('/webhooks/moota', MootaWebhookController::class)
    ->name('webhooks.moota');

Route::get('/layanan', [MuthowifDirectoryController::class, 'index'])->name('layanan.index');
Route::get('/layanan/{publicProfile}/foto', [MuthowifDirectoryController::class, 'photo'])->name('layanan.photo');
Route::get('/layanan/portfolio/{portfolio}/foto', [MuthowifDirectoryController::class, 'portfolioPhoto'])->name('layanan.portfolio.photo');
Route::get('/layanan/portfolio/foto/{image}', [MuthowifDirectoryController::class, 'portfolioImage'])->name('layanan.portfolio.image');
Route::get('/layanan/{publicProfile}/booking', [MuthowifDirectoryController::class, 'booking'])->name('layanan.book');
Route::get('/layanan/{publicProfile}/portfolio', [MuthowifDirectoryController::class, 'portfolioIndex'])->name('layanan.portfolio.index');
Route::get('/layanan/{publicProfile}', [MuthowifDirectoryController::class, 'show'])->name('layanan.show');

Route::get('/terms', TermsController::class)->name('terms');
Route::get('/artikel', [ArticleController::class, 'index'])->name('articles.index');
Route::get('/artikel/{slug}', [ArticleController::class, 'show'])->name('articles.show');
Route::get('/campaign/{slug}', [CampaignController::class, 'show'])->name('campaigns.show');

Route::get('/locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

Route::get('/sitemap.xml', [SeoLandingController::class, 'sitemapIndex'])->name('seo.sitemap.index');
Route::get('/sitemap-{type}.xml', [SeoLandingController::class, 'sitemapPage'])
    ->where('type', 'home|categories|services|articles');
Route::get('/sitemap-{type}-{page}.xml', [SeoLandingController::class, 'sitemapPage'])
    ->where('type', 'home|categories|services|articles')
    ->where('page', '[0-9]+')
    ->name('seo.sitemap.page');

Route::get('/', WelcomeController::class)->name('welcome');

/** Hanya local: beberapa panel uji hanya bisa POST ke root URL tunnel (tanpa path). */
if (app()->environment('local')) {
    Route::withoutMiddleware([ValidateCsrfToken::class])
        ->post('/', function (Request $request) {
            Log::info('webhooks.dev_root_ping', [
                'ip' => $request->ip(),
                'content_type' => $request->header('Content-Type'),
                'raw_preview' => Str::limit($request->getContent(), 8192),
            ]);

            return response()->json([
                'ok' => true,
                'received_at' => now()->toIso8601String(),
                'hint' => 'Untuk uji rutin lebih baik pakai POST '.rtrim(config('app.url'), '/').'/webhooks/test',
                'real_webhooks' => [
                    'doku' => route('payments.doku.notification', absolute: true),
                    'midtrans' => route('payments.midtrans.notification', absolute: true),
                ],
            ]);
        })->name('webhooks.dev_root_ping');
}

Route::get('/muthowif/daftar/menunggu', function () {
    return view('auth.muthowif-registration-pending');
})->name('muthowif.registration.pending');

Route::get('/perusahaan/daftar/menunggu', function () {
    $pendingId = session('pending_company_id');
    if (! $pendingId) {
        return redirect()->route('login');
    }

    return view('auth.company-registration-pending', ['pendingId' => $pendingId]);
})->name('company.registration.pending');

Route::get('/dashboard', function () {
    $activeCampaigns = Campaign::active()
        ->orderBy('sort_order')
        ->orderByDesc('start_date')
        ->get();

    return view('dashboard', compact('activeCampaigns'));
})->middleware(['auth'])->name('dashboard');

Route::get('/dashboard/muthowif-calendar', MuthowifDashboardCalendarController::class)
    ->middleware(['auth'])
    ->name('dashboard.muthowif-calendar');

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
    Route::get('/profile/public/photo', [ProfileController::class, 'publicPhoto'])->name('profile.public.photo');
    Route::get('/profile/public/ktp', [ProfileController::class, 'publicKtp'])->name('profile.public.ktp');
    Route::get('/profile/public/documents/{document}', [ProfileController::class, 'publicSupportingDocument'])
        ->name('profile.public.document');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/chat/conversations', [GlobalChatController::class, 'index'])->name('chat.conversations');

    Route::middleware(['reporter'])->prefix('support')->name('support.')->group(function () {
        Route::get('live-index-fragment', [SupportTicketController::class, 'indexLiveFragment'])->name('index.live-fragment');
        Route::get('/', [SupportTicketController::class, 'index'])->name('index');
        Route::get('/baru', [SupportTicketController::class, 'create'])->name('create');
        Route::post('/', [SupportTicketController::class, 'store'])->name('store');
        Route::get('/{ticket}/fragment', [SupportTicketController::class, 'showLiveFragment'])->name('show.fragment');
        Route::get('/{ticket}', [SupportTicketController::class, 'show'])->name('show');
        Route::post('/{ticket}/balas', [SupportTicketController::class, 'reply'])->name('reply');
    });

    Route::middleware([EnsureUserRole::class.':customer'])->prefix('bookings')->name('bookings.')->group(function () {
        Route::get('live-index-fragment', [CustomerBookingController::class, 'indexLiveFragment'])->name('index.live-fragment');
        Route::get('/', [CustomerBookingController::class, 'index'])->name('index');
        Route::post('/', [CustomerBookingController::class, 'store'])->name('store');
        Route::get('{booking}/live-state', [CustomerBookingController::class, 'showLiveState'])->name('show.live-state');
        Route::get('{booking}/fragment', [CustomerBookingController::class, 'showLiveFragment'])->name('show.fragment');
        Route::get('{booking}/pembayaran', [CustomerBookingController::class, 'payment'])->name('payment');
        Route::get('{booking}/payment-status', [CustomerBookingController::class, 'paymentStatus'])->name('payment.status');
        Route::get('{booking}/invoice', [CustomerBookingController::class, 'invoice'])->name('invoice');
        Route::post('{booking}/selesaikan', [CustomerBookingController::class, 'complete'])->name('complete');
        Route::post('{booking}/review', [CustomerBookingController::class, 'review'])->name('review');
        Route::get('{booking}/refund', [CustomerBookingController::class, 'requestRefund'])->name('refund');
        Route::get('{booking}/reschedule', [CustomerBookingController::class, 'requestReschedule'])->name('reschedule');
        Route::post('{booking}/refund-request', [CustomerBookingController::class, 'storeRefundRequest'])->name('refund_request.store');
        Route::post('{booking}/reschedule-request', [CustomerBookingController::class, 'storeRescheduleRequest'])->name('reschedule_request.store');
        Route::post('{booking}/emergency-report', [CustomerBookingEmergencyController::class, 'store'])->name('emergency.store');
        Route::post('{booking}/emergency-select/{offer}', [CustomerBookingEmergencyController::class, 'selectReplacement'])->name('emergency.select');
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

            Route::get('portfolio', [MuthowifPortfolioController::class, 'index'])->name('portfolio.index');
            Route::post('portfolio', [MuthowifPortfolioController::class, 'store'])->name('portfolio.store');
            Route::get('portfolio/images/{image}', [MuthowifPortfolioController::class, 'image'])->name('portfolio.image');
            Route::patch('portfolio/{portfolio}', [MuthowifPortfolioController::class, 'update'])->name('portfolio.update');
            Route::delete('portfolio/{portfolio}', [MuthowifPortfolioController::class, 'destroy'])->name('portfolio.destroy');

            Route::get('bookings/live-index-fragment', [MuthowifBookingController::class, 'indexLiveFragment'])->name('bookings.index.live-fragment');
            Route::get('bookings/pending-incoming-count', [MuthowifBookingController::class, 'pendingIncomingCount'])->name('bookings.pending-incoming-count');
            Route::get('bookings', [MuthowifBookingController::class, 'index'])->name('bookings.index');
            Route::get('bookings/{booking}/live-state', [MuthowifBookingController::class, 'showLiveState'])->name('bookings.show.live-state');
            Route::get('bookings/{booking}/fragment', [MuthowifBookingController::class, 'showLiveFragment'])->name('bookings.show.fragment');
            Route::get('bookings/{booking}/chat/messages', [BookingChatController::class, 'index'])->name('bookings.chat.messages');
            Route::get('bookings/{booking}/chat/unread-count', [BookingChatController::class, 'unreadCount'])->name('bookings.chat.unread-count');
            Route::post('bookings/{booking}/chat/messages', [BookingChatController::class, 'store'])->name('bookings.chat.messages.store');
            Route::get('bookings/{booking}/chat/messages/{message}/image', [BookingChatController::class, 'image'])->name('bookings.chat.messages.image');
            Route::get('bookings/{booking}/documents/{type}', [CustomerBookingController::class, 'downloadDocument'])
                ->where('type', 'outbound|return|passport|itinerary|visa')
                ->name('bookings.documents.show');
            Route::post('bookings/{booking}/recommend-peer', [MuthowifBookingController::class, 'recommendToPeer'])->name('bookings.recommend-peer');
            Route::get('bookings/{booking}', [MuthowifBookingController::class, 'show'])->name('bookings.show');
            Route::post('bookings/{booking}/confirm', [MuthowifBookingController::class, 'confirm'])->name('bookings.confirm');
            Route::post('bookings/{booking}/cancel', [MuthowifBookingController::class, 'cancel'])->name('bookings.cancel');
            Route::post('bookings/{booking}/reschedule-requests/{rescheduleRequest}/approve', [MuthowifBookingController::class, 'approveReschedule'])->name('bookings.reschedule_requests.approve');
            Route::post('bookings/{booking}/reschedule-requests/{rescheduleRequest}/reject', [MuthowifBookingController::class, 'rejectReschedule'])->name('bookings.reschedule_requests.reject');

            Route::get('emergency-offers/live-index-fragment', [EmergencyOfferController::class, 'indexLiveFragment'])->name('emergency-offers.index.live-fragment');
            Route::get('emergency-offers/pending-offer-count', [EmergencyOfferController::class, 'pendingOfferCount'])->name('emergency-offers.pending-offer-count');
            Route::get('emergency-offers', [EmergencyOfferController::class, 'index'])->name('emergency-offers.index');
            Route::post('emergency-offers/{offer}/accept', [EmergencyOfferController::class, 'accept'])->name('emergency-offers.accept');
            Route::post('emergency-offers/{offer}/decline', [EmergencyOfferController::class, 'decline'])->name('emergency-offers.decline');

            Route::get('withdrawals/live-index-fragment', [MuthowifWithdrawController::class, 'indexLiveFragment'])->name('withdrawals.index.live-fragment');
            Route::get('withdrawals', [MuthowifWithdrawController::class, 'index'])->name('withdrawals.index');
            Route::post('withdrawals', [MuthowifWithdrawController::class, 'store'])->name('withdrawals.store');
        });

    Route::middleware([EnsureUserRole::class.':admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('pengaturan', AdminSettingsHubController::class)->name('settings.index');
        Route::get('whatsapp-broadcast', [WhatsAppBroadcastController::class, 'index'])->name('whatsapp-broadcast.index');
        Route::post('whatsapp-broadcast/kirim', [WhatsAppBroadcastController::class, 'send'])->name('whatsapp-broadcast.send');
        Route::post('artikel/ckeditor/unggah', [ArticlesAdminController::class, 'ckeditorUpload'])->name('articles.ckeditor_upload');
        Route::post('artikel/editorjs/unggah', [ArticlesAdminController::class, 'editorjsUpload'])->name('articles.editorjs_upload');
        Route::resource('artikel', ArticlesAdminController::class)
            ->parameters(['artikel' => 'article'])
            ->except(['show'])
            ->names('articles');
        Route::get('tampilan/logo', [SiteAppearanceController::class, 'edit'])->name('site-appearance.edit');
        Route::post('tampilan/logo', [SiteAppearanceController::class, 'update'])->name('site-appearance.update');
        Route::resource('campaign', CampaignsAdminController::class);
        Route::get('pengguna', [UserManagementController::class, 'index'])->name('users.index');
        Route::get('pengguna/{user}/ubah', [UserManagementController::class, 'edit'])->name('users.edit');
        Route::patch('pengguna/{user}', [UserManagementController::class, 'update'])->name('users.update');
        Route::get('perusahaan-menunggu', [CompanyApprovalController::class, 'index'])->name('company_approval.index');
        Route::post('perusahaan-menunggu/{user}/approve', [CompanyApprovalController::class, 'approve'])->name('company_approval.approve');
        Route::get('pantau-layanan', [ServiceMonitorController::class, 'index'])->name('service_monitor.index');
        Route::get('pantau-layanan/fragment', [ServiceMonitorController::class, 'fragment'])->name('service_monitor.fragment');
        Route::get('insiden-darurat/live-index-fragment', [BookingEmergencyController::class, 'indexLiveFragment'])->name('emergency.index.live-fragment');
        Route::get('insiden-darurat', [BookingEmergencyController::class, 'index'])->name('emergency.index');
        Route::get('insiden-darurat/{report}/fragment', [BookingEmergencyController::class, 'showLiveFragment'])->name('emergency.show.fragment');
        Route::get('insiden-darurat/{report}', [BookingEmergencyController::class, 'show'])->name('emergency.show');
        Route::post('insiden-darurat/{report}/tinjau', [BookingEmergencyController::class, 'markUnderReview'])->name('emergency.under_review');
        Route::post('insiden-darurat/{report}/verifikasi', [BookingEmergencyController::class, 'verify'])->name('emergency.verify');
        Route::post('insiden-darurat/{report}/tolak', [BookingEmergencyController::class, 'reject'])->name('emergency.reject');
        Route::post('insiden-darurat/{report}/broadcast', [BookingEmergencyController::class, 'broadcastBatch'])->name('emergency.broadcast');
        Route::post('insiden-darurat/{report}/undang', [BookingEmergencyController::class, 'invite'])->name('emergency.invite');
        Route::get('refund-menunggu', [BookingRefundController::class, 'index'])->name('refunds.index');
        Route::post('refund-menunggu/{refund}/selesai', [BookingRefundController::class, 'complete'])->name('refunds.complete');
        Route::get('keuangan', [FinanceController::class, 'index'])->name('finance.index');
        Route::get('moota-webhooks/testing', [MootaWebhookHistoriesLiveController::class, 'testing'])
            ->name('moota_webhooks.testing');
        Route::get('moota-webhooks', [MootaWebhookHistoriesLiveController::class, 'live'])
            ->name('moota_webhooks.live');
        Route::get('withdrawals', [WithdrawalsController::class, 'index'])->name('withdrawals.index');
        Route::get('withdrawals/fragment', [WithdrawalsController::class, 'indexFragment'])->name('withdrawals.fragment');
        Route::post('withdrawals/{withdrawal}/approve', [WithdrawalsController::class, 'approve'])->name('withdrawals.approve');
        Route::post('withdrawals/{withdrawal}/selesai-transfer', [WithdrawalsController::class, 'markTransferred'])->name('withdrawals.mark_transferred');
        Route::post('withdrawals/{withdrawal}/gagal-transfer', [WithdrawalsController::class, 'markTransferFailed'])->name('withdrawals.mark_transfer_failed');
        Route::get('muthowif/live-index-fragment', [MuthowifVerificationController::class, 'indexLiveFragment'])->name('muthowif.index.live-fragment');
        Route::get('muthowif', [MuthowifVerificationController::class, 'index'])->name('muthowif.index');
        Route::get('muthowif/{profile}/photo', [MuthowifVerificationController::class, 'photo'])->name('muthowif.photo');
        Route::get('muthowif/{profile}/ktp', [MuthowifVerificationController::class, 'ktp'])->name('muthowif.ktp');
        Route::get('muthowif/{profile}/documents/{document}', [MuthowifVerificationController::class, 'supportingDocument'])->name('muthowif.document');
        Route::post('muthowif/{profile}/approve', [MuthowifVerificationController::class, 'approve'])->name('muthowif.approve');
        Route::post('muthowif/{profile}/reject', [MuthowifVerificationController::class, 'reject'])->name('muthowif.reject');
        Route::post('muthowif/{profile}/account-status', [MuthowifVerificationController::class, 'updateAccountStatus'])->name('muthowif.account_status');
        Route::get('muthowif/{profile}', [MuthowifVerificationController::class, 'show'])->name('muthowif.show');
        Route::get('tiket/live-index-fragment', [SupportTicketsController::class, 'indexLiveFragment'])->name('support-tickets.index.live-fragment');
        Route::get('tiket', [SupportTicketsController::class, 'index'])->name('support-tickets.index');
        Route::get('tiket/{ticket}/fragment', [SupportTicketsController::class, 'showLiveFragment'])->name('support-tickets.show.fragment');
        Route::get('tiket/{ticket}', [SupportTicketsController::class, 'show'])->name('support-tickets.show');
        Route::post('tiket/{ticket}/balas', [SupportTicketsController::class, 'reply'])->name('support-tickets.reply');
        Route::patch('tiket/{ticket}', [SupportTicketsController::class, 'update'])->name('support-tickets.update');
        Route::post('tiket/{ticket}/tugaskan-saya', [SupportTicketsController::class, 'assignSelf'])->name('support-tickets.assign_self');
    });
});

require __DIR__.'/auth.php';

Route::get('/muthowif/{keyword}', [SeoLandingController::class, 'showKeyword'])
    ->where('keyword', '[a-z0-9\-]+')
    ->name('seo.landing');

Route::get('/php-test', function () {
    return [
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
    ];
});
