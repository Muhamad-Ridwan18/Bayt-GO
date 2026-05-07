<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\BookingPayment;
use App\Models\MuthowifBooking;
use App\Payments\Moota\MootaSnapPaymentProvider;
use App\Services\Moota\MootaApiClient;
use App\Support\PlatformFee;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Menjalankan alur BaytGo → Moota Create Transaction untuk booking nyata,
 * lalu opsional mengirim POST /webhooks/moota yang meniru mutasi kredit (QA lokal).
 */
class MootaTestBookingPaymentCommand extends Command
{
    protected $signature = 'moota:test-booking-payment
                            {booking_id? : UUID booking (Confirmed + bayar pending); default: booking pertama yang cocok}
                            {--post-local-webhook : Setelah charge, POST payload mutasi ke app (butuh MOOTA_WEBHOOK_IPS + URL bisa dijangkau)}
                            {--allow-production : Izinkan jalan walau APP_ENV=production (hati-hati)}';

    protected $description = 'Buat transaksi pembayaran Moota untuk booking tes: cetak payment_url & order_id; opsional simulasikan webhook mutasi.';

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('allow-production')) {
            $this->error('Di production pakai `--allow-production` jika Anda yakin.');

            return self::FAILURE;
        }

        /** @var MootaSnapPaymentProvider $provider */
        $provider = app(MootaSnapPaymentProvider::class);

        if (! $provider->isConfigured()) {
            $this->error('Moota tidak dikonfigurasi (env API + MOOTA_BANK_ACCOUNT_ID).');

            return self::FAILURE;
        }

        $bookingId = $this->argument('booking_id');
        $booking = null;

        if (is_string($bookingId) && $bookingId !== '') {
            $booking = MuthowifBooking::query()->whereKey($bookingId)->first();
            if ($booking === null) {
                $this->error('Booking tidak ditemukan: '.$bookingId);

                return self::FAILURE;
            }
        } else {
            $booking = MuthowifBooking::query()
                ->where('status', BookingStatus::Confirmed)
                ->where('payment_status', PaymentStatus::Pending)
                ->orderByDesc('created_at')
                ->first();

            if ($booking === null) {
                $this->error('Tidak ada booking Confirmed dengan payment_status=pending. Buat/sahkan booking dulu.');

                return self::FAILURE;
            }
        }

        if ($booking->status !== BookingStatus::Confirmed || $booking->payment_status !== PaymentStatus::Pending) {
            $this->error('Booking harus Confirmed dan belum lunas (payment pending). Status saat ini: '
                .$booking->status->value.' / '.$booking->payment_status->value);

            return self::FAILURE;
        }

        $baseInt = (int) round($booking->resolvedAmountDue());
        if ($baseInt < 1) {
            $this->error('Nominal booking tidak valid (resolvedAmountDue < 1).');

            return self::FAILURE;
        }

        $superseded = $booking->bookingPayments()->where('status', 'pending')->update(['status' => 'cancelled']);
        if ($superseded > 0) {
            $this->line('Pending lama ditandai cancelled: '.$superseded.' baris.');
        }

        $split = PlatformFee::split((float) $baseInt);
        $orderId = 'BG-'.str_replace('-', '', (string) $booking->getKey()).'-'.Str::lower(Str::random(10));

        $payment = BookingPayment::query()->create([
            'muthowif_booking_id' => $booking->getKey(),
            'order_id' => $orderId,
            'gross_amount' => (int) round($split['customer_gross']),
            'platform_fee_amount' => $split['platform_fee_total'],
            'muthowif_net_amount' => $split['muthowif_net'],
            'status' => 'pending',
        ]);

        try {
            $mootaIds = app(MootaApiClient::class)->bankAccountIds();
            $mootaExplicit = \count($mootaIds) > 1 ? $mootaIds[0] : null;
            $session = $provider->createPaymentSession($payment, 'bank_transfer_moota', $mootaExplicit);
        } catch (RuntimeException $e) {
            $this->error('Create transaction gagal: '.$e->getMessage());

            return self::FAILURE;
        }

        $payment = $payment->fresh();
        if ($payment === null) {
            $this->error('Pembayaran hilang setelah charge.');

            return self::FAILURE;
        }

        $this->table(
            ['Field', 'Value'],
            [
                ['booking_id', (string) $booking->getKey()],
                ['booking_code', (string) ($booking->booking_code ?? '')],
                ['order_id', $payment->order_id],
                ['gross_amount (IDR)', (string) $payment->gross_amount],
                ['trx_id', (string) ($payment->gateway_transaction_id ?? '')],
                ['payment_url', $session->paymentUrl ?? ''],
            ],
        );

        $this->newLine();
        $this->line('Agar mutasi terlihat di dashboard Moota (sandbox bank transfer): buka payment_url di browser '
            .'dan selesaikan alur simulasi pembayaran di halaman/hosting mereka (lihat dokumentasi akun sandbox Moota).');

        if ($this->option('post-local-webhook')) {
            return $this->postLocalSettlementWebhook($payment) ? self::SUCCESS : self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function postLocalSettlementWebhook(BookingPayment $payment): bool
    {
        /** @var list<string>|array<int, string> $allowed */
        $allowed = config('services.moota.webhook_ips', []);
        if ($allowed === []) {
            $this->error('MOOTA_WEBHOOK_IPS kosong; webhook akan ditolak middleware.');

            return false;
        }

        $spoofIp = (string) reset($allowed);
        $expected = $this->expectedMootaTotal($payment);

        if ($expected === null) {
            $this->error('Tidak bisa membaca nominal Moota dari gateway_notification_payload (charge belum lengkap?).');

            return false;
        }

        $trxId = $payment->gateway_transaction_id;
        if (! is_string($trxId) || $trxId === '') {
            $this->error('gateway_transaction_id (trx_id) kosong.');

            return false;
        }

        $mutation = [
            'type' => 'CR',
            'mutation_id' => 'local-test-'.Str::uuid()->toString(),
            'amount' => $expected,
            'payment_detail' => [
                'order_id' => $payment->order_id,
                'trx_id' => $trxId,
                'total' => $expected,
                'amount_captured' => $expected,
            ],
        ];

        $payloadList = [$mutation];
        $raw = json_encode($payloadList, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $headers = [
            'Content-Type' => 'application/json',
            'CF-Connecting-IP' => $spoofIp,
        ];

        $secret = (string) config('services.moota.signing_secret', '');
        if ($secret !== '') {
            $headers['Signature'] = hash_hmac('sha256', $raw, $secret);
        }

        $url = route('webhooks.moota', [], true);
        $this->line('POST webhook ke: '.$url);

        try {
            $response = Http::timeout(60)
                ->withHeaders($headers)
                ->withBody($raw, 'application/json')
                ->post($url);
        } catch (\Throwable $e) {
            $this->error('Request webhook gagal: '.$e->getMessage());

            return false;
        }

        if ($response->failed()) {
            $this->error('Webhook HTTP '.$response->status().': '.$response->body());

            return false;
        }

        $booking = $payment->muthowifBooking;
        $booking?->refresh();
        $payment->refresh();

        $this->line('Booking payment_status setelah webhook: '
            .(($booking !== null) ? $booking->payment_status->value : 'n/a'));
        $this->line('BookingPayment status: '.$payment->status);

        if (($booking !== null && $booking->payment_status !== PaymentStatus::Paid) || ! $payment->isSettled()) {
            $this->warn('Webhook diterima tapi pembayaran belum settle — cek log (nominal trx), signature, atau middleware IP.');

            return false;
        }

        $this->info('Pembayaran ter-settle via webhook uji.');

        return true;
    }

    /**
     * Sama seperti listener: nominal yang diharapkan dari respons create-transaction di metadata payment.
     */
    private function expectedMootaTotal(BookingPayment $payment): ?int
    {
        $gatewayMeta = $payment->gateway_notification_payload ?? [];
        if (! is_array($gatewayMeta)) {
            return null;
        }

        $root = $gatewayMeta['moota_create_transaction_response'] ?? null;
        if (! is_array($root)) {
            return null;
        }

        $data = $root['data'] ?? null;
        if (! is_array($data)) {
            $data = $root;
        }

        $total = data_get($data, 'total');
        if (is_numeric($total)) {
            return (int) round((float) $total);
        }

        return null;
    }
}
