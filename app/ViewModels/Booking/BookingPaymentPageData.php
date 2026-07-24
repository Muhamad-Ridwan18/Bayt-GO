<?php

namespace App\ViewModels\Booking;

use App\Models\MuthowifBooking;
use App\Support\AffiliateBankOptions;
use App\Support\BookingSnapPaymentCatalog;
use App\Support\IndonesianNumber;
use App\Support\PlatformFee;

final class BookingPaymentPageData
{
    /**
     * @param  list<string>  $methods
     * @param  list<string>  $mootaBankAccountIds
     * @param  array<string, array{title: string, description: string}>  $methodGroups
     * @param  list<array<string, mixed>>  $methodsUi
     */
    public function __construct(
        public readonly MuthowifBooking $booking,
        public readonly string $selectedMethod,
        public readonly mixed $instructions,
        public readonly bool $isWaitingConfirmation,
        public readonly array $methods,
        public readonly array $mootaBankAccountIds,
        public readonly bool $isCompany,
        public readonly float $customerPlatformFee,
        public readonly float $customerTotal,
        public readonly array $methodGroups,
        public readonly array $methodsUi,
        public readonly string $paymentDriver,
        public readonly string $tripDateRangeLabel,
        public readonly ?string $expiryFormatted,
        public readonly string $muthowifName,
        public readonly string $bookingCode,
    ) {}

    /**
     * @param  list<string>  $methods
     * @param  list<string>  $mootaBankAccountIds
     * @param  array<int, array{name: string, description: string, logo_url?: string}>  $mootaPaymentRows
     */
    public static function make(
        MuthowifBooking $booking,
        string $selectedMethod,
        array $methods,
        array $mootaBankAccountIds,
        array $mootaPaymentRows,
        mixed $instructions,
    ): self {
        $isWaitingConfirmation = $selectedMethod !== '' && is_array($instructions);

        if (BookingSnapPaymentCatalog::driver() === 'moota') {
            $mootaIdsForUi = array_values(array_filter(array_map(trim(...), config('services.moota.bank_account_ids', []))));
            $mootaBankAccountIds = $mootaIdsForUi;
            if (count($mootaIdsForUi) > 1) {
                $methods = array_map(
                    static fn (int $i): string => 'bank_transfer_moota__'.$i,
                    array_keys($mootaIdsForUi)
                );
            }
        }

        $isCompany = $booking->customer?->isCompanyCustomer() ?? false;
        $split = PlatformFee::split((float) $booking->resolvedAmountDue(), $isCompany);
        $customerPlatformFee = (float) ($split['customer_fee'] ?? 0.0);
        $customerTotal = (float) ($split['customer_gross'] ?? 0.0);

        $bankLogo = static fn (string $code): string => AffiliateBankOptions::logoUrl($code)
            ?? asset('images/payments/bank_transfer_moota.svg');

        $methodGroups = [
            'bank' => [
                'title' => __('bookings.payment.groups.bank.title'),
                'description' => __('bookings.payment.groups.bank.description'),
            ],
            'ewallet' => [
                'title' => __('bookings.payment.groups.ewallet.title'),
                'description' => __('bookings.payment.groups.ewallet.description'),
            ],
            'qris' => [
                'title' => __('bookings.payment.groups.qris.title'),
                'description' => __('bookings.payment.groups.qris.description'),
            ],
            'moota' => [
                'title' => __('bookings.payment.groups.moota.title'),
                'description' => __('bookings.payment.groups.moota.description'),
            ],
        ];

        $methodsUi = [
            [
                'id' => 'va_bca',
                'group' => 'bank',
                'name' => __('bookings.payment.method_va_bca.name'),
                'logo_path' => $bankLogo('BCA'),
                'description' => __('bookings.payment.method_va_bca.description'),
                'enabled' => in_array('va_bca', $methods, true),
            ],
            [
                'id' => 'va_bni',
                'group' => 'bank',
                'name' => __('bookings.payment.method_va_bni.name'),
                'logo_path' => $bankLogo('BNI'),
                'description' => __('bookings.payment.method_va_bni.description'),
                'enabled' => in_array('va_bni', $methods, true),
            ],
            [
                'id' => 'va_bri',
                'group' => 'bank',
                'name' => __('bookings.payment.method_va_bri.name'),
                'logo_path' => $bankLogo('BRI'),
                'description' => __('bookings.payment.method_va_bri.description'),
                'enabled' => in_array('va_bri', $methods, true),
            ],
            [
                'id' => 'va_permata',
                'group' => 'bank',
                'name' => __('bookings.payment.method_va_permata.name'),
                'logo_path' => $bankLogo('Permata'),
                'description' => __('bookings.payment.method_va_permata.description'),
                'enabled' => in_array('va_permata', $methods, true),
            ],
            [
                'id' => 'va_mandiri_bill',
                'group' => 'bank',
                'name' => __('bookings.payment.method_va_mandiri_bill.name'),
                'logo_path' => $bankLogo('Mandiri'),
                'description' => __('bookings.payment.method_va_mandiri_bill.description'),
                'enabled' => in_array('va_mandiri_bill', $methods, true),
            ],
            [
                'id' => 'bank_transfer_moota',
                'group' => 'moota',
                'name' => __('bookings.payment.method_bank_transfer_moota.name'),
                'logo_path' => asset('images/payments/bank_transfer_moota.svg'),
                'description' => __('bookings.payment.method_bank_transfer_moota.description'),
                'enabled' => in_array('bank_transfer_moota', $methods, true),
            ],
            [
                'id' => 'qris',
                'group' => 'qris',
                'name' => __('bookings.payment.method_qris.name'),
                'logo_path' => asset('images/payments/qris.svg'),
                'description' => __('bookings.payment.method_qris.description'),
                'enabled' => in_array('qris', $methods, true),
            ],
            [
                'id' => 'gopay',
                'group' => 'ewallet',
                'name' => __('bookings.payment.method_gopay.name'),
                'logo_path' => asset('images/payments/gopay.svg'),
                'description' => __('bookings.payment.method_gopay.description'),
                'enabled' => in_array('gopay', $methods, true),
            ],
            [
                'id' => 'shopeepay',
                'group' => 'ewallet',
                'name' => __('bookings.payment.method_shopeepay.name'),
                'logo_path' => asset('images/payments/shopeepay.svg'),
                'description' => __('bookings.payment.method_shopeepay.description'),
                'enabled' => in_array('shopeepay', $methods, true),
            ],
        ];

        $mootaExtras = [];
        foreach ($methods as $mid) {
            if (preg_match('/^bank_transfer_moota__(\d+)$/', (string) $mid, $mm)) {
                $mi = (int) $mm[1];
                $mootaExtras[] = [
                    'id' => $mid,
                    'group' => 'moota',
                    'name' => __('bookings.payment.moota_account_title', ['n' => $mi + 1]),
                    'logo_path' => asset('images/payments/bank_transfer_moota.svg'),
                    'description' => '',
                    'enabled' => true,
                ];
            }
        }
        if ($mootaExtras !== []) {
            $methodsUi = array_values(array_merge(
                array_values(array_filter($methodsUi, static fn (array $row): bool => $row['id'] !== 'bank_transfer_moota')),
                $mootaExtras
            ));
        }

        if (BookingSnapPaymentCatalog::driver() === 'moota' && $mootaPaymentRows !== []) {
            $methodsUi = array_map(static function (array $row) use ($mootaPaymentRows): array {
                if (($row['group'] ?? '') !== 'moota') {
                    return $row;
                }
                if (preg_match('/^bank_transfer_moota__(\d+)$/', (string) ($row['id'] ?? ''), $m)) {
                    $mi = (int) $m[1];
                    if (isset($mootaPaymentRows[$mi])) {
                        $row['name'] = $mootaPaymentRows[$mi]['name'];
                        $row['description'] = $mootaPaymentRows[$mi]['description'];
                        if (! empty($mootaPaymentRows[$mi]['logo_url'])) {
                            $row['logo_path'] = $mootaPaymentRows[$mi]['logo_url'];
                        }
                    }
                } elseif (($row['id'] ?? '') === 'bank_transfer_moota' && isset($mootaPaymentRows[0])) {
                    $row['name'] = $mootaPaymentRows[0]['name'];
                    $row['description'] = $mootaPaymentRows[0]['description'];
                    if (! empty($mootaPaymentRows[0]['logo_url'])) {
                        $row['logo_path'] = $mootaPaymentRows[0]['logo_url'];
                    }
                }

                return $row;
            }, $methodsUi);
        }

        $tripDateRangeLabel = '';
        try {
            if ($booking->starts_on && $booking->ends_on) {
                $tripDateRangeLabel = $booking->starts_on->format('d/m/Y').' – '.$booking->ends_on->format('d/m/Y');
            }
        } catch (\Throwable) {
            $tripDateRangeLabel = '';
        }

        $expiryFormatted = null;
        if (is_array($instructions) && ! empty($instructions['expiry_time'])) {
            try {
                $expiryFormatted = \Carbon\Carbon::parse($instructions['expiry_time'])
                    ->timezone(config('app.timezone'))
                    ->format('d M Y, H:i');
            } catch (\Throwable) {
                $expiryFormatted = null;
            }
        }

        return new self(
            booking: $booking,
            selectedMethod: $selectedMethod,
            instructions: $instructions,
            isWaitingConfirmation: $isWaitingConfirmation,
            methods: $methods,
            mootaBankAccountIds: $mootaBankAccountIds,
            isCompany: $isCompany,
            customerPlatformFee: $customerPlatformFee,
            customerTotal: $customerTotal,
            methodGroups: $methodGroups,
            methodsUi: $methodsUi,
            paymentDriver: BookingSnapPaymentCatalog::driver(),
            tripDateRangeLabel: $tripDateRangeLabel,
            expiryFormatted: $expiryFormatted,
            muthowifName: (string) ($booking->muthowifProfile?->user?->name ?? '—'),
            bookingCode: (string) ($booking->booking_code ?? ''),
        );
    }

    public function formatMoney(float $amount): string
    {
        return IndonesianNumber::formatThousands((string) (int) round($amount));
    }

    public function customerTotalFormatted(): string
    {
        return $this->formatMoney($this->customerTotal);
    }

    public function customerPlatformFeeFormatted(): string
    {
        return $this->formatMoney($this->customerPlatformFee);
    }

    public function isMootaDriver(): bool
    {
        return $this->paymentDriver === 'moota';
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function enabledMethodsForGroup(string $groupId): array
    {
        return array_values(array_filter(
            $this->methodsUi,
            static fn (array $item): bool => ($item['enabled'] ?? false) && ($item['group'] ?? '') === $groupId
        ));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function groupContainsSelectedMethod(string $groupId): bool
    {
        if ($this->selectedMethod === '') {
            return false;
        }

        foreach ($this->enabledMethodsForGroup($groupId) as $method) {
            if (($method['id'] ?? '') === $this->selectedMethod) {
                return true;
            }
        }

        return false;
    }
}
