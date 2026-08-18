# Clean Code Examples — BaytGo

Referensi kode Laravel paling rapi di project ini, plus arahan agar lebih clean.

**Prinsip yang sudah jalan di repo:**

| Layer | Peran |
|-------|--------|
| **Controller** | Orkestrasi HTTP saja (auth → service/viewmodel → response) |
| **Form Request** | Validasi + authorize + normalisasi input |
| **Policy** | Satu method = satu aksi bisnis |
| **Service** | Domain logic, transaksi, side-effect |
| **Model** | Relasi, cast, predicate kecil (`isPaid()`, `billingNightsInclusive()`) |
| **Enum** | Status/tipe + helper (`label()`, `isOpen()`) |
| **ViewModel** | Bentuk data untuk Blade, bukan di controller |
| **Listener / Job** | Tipis: load → panggil service/notifier |

---

## Peta lokasi (yang layak ditiru)

```
app/
├── Enums/                          ← status & tipe domain
├── Http/
│   ├── Controllers/Admin/          ← contoh thin controller
│   ├── Requests/                   ← Form Request
│   └── Requests/Auth/
├── Policies/                       ← authorization per aksi
├── Services/                       ← business logic
│   ├── Admin/
│   ├── Doku/
│   ├── Emergency/                  ← calculator murni + eligibility
│   └── Moota/
├── Models/                         ← domain helpers kecil
├── ViewModels/                     ← data Blade (Auth, Booking, Dashboard, Layanan)
├── Events/ + Listeners/ + Jobs/    ← side-effect tipis
└── Support/                        ← helper murni (phone, pricing view, broadcast)
```

---

## 1. Thin Controller

**File:** `app/Http/Controllers/Admin/ServiceMonitorController.php`

```php
final class ServiceMonitorController extends Controller
{
    public function __construct(
        private readonly AdminServiceMonitorService $monitor,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $feed = $this->monitor->feed($request);

        return view('admin.service-monitor.index', [
            ...$feed,
            'realtimeEnabled' => config('broadcasting.default') !== 'null',
        ]);
    }
}
```

**Kenapa rapi**

- Constructor DI + `readonly`
- Tidak ada query / kalkulasi di controller
- Payload view datang dari service

**Agar lebih clean**

- Pindahkan `abort_unless(...isAdmin())` ke middleware / policy
- Controller hanya: `$this->monitor->feed()` → `view(...)`

---

## 2. Service layer (domain murni)

**File:** `app/Services/BookingPricingService.php`

```php
public function calculateTotal(MuthowifBooking $booking): float
{
    if ($booking->total_amount !== null) {
        return (float) $booking->total_amount;
    }

    return $this->calculateBaseFromComponents($booking);
}

public function calculateBaseFromComponents(MuthowifBooking $booking): float
{
    if ($booking->service_type === MuthowifServiceType::Support) {
        return round((float) ($booking->package_price_snapshot ?? 0), 2);
    }

    $nights = $booking->billingNightsInclusive();
    $base = $nights * $this->getDailyPrice($booking);
    // ... add-ons, hotel, transport via private helpers

    return round($base + $addons + $sameHotel + $transport, 2);
}
```

**Kenapa rapi**

- API publik jelas: `calculateTotal`, `calculateBaseFromComponents`, `getPricingSnapshots`
- Snapshot-first → live-fallback
- Private helpers per komponen harga
- Hampir tanpa side-effect HTTP

**Agar lebih clean**

- Pisahkan strategi Support vs Private (method/`match` kecil) agar `getPricingSnapshots()` tidak terus bercabang

**File sejenis (juga bagus):**

| Path | Fokus |
|------|--------|
| `app/Services/Emergency/EmergencySettlementCalculator.php` | Kalkulasi murni, zero I/O |
| `app/Services/AffiliateCommissionService.php` | Lifecycle + `lockForUpdate` + idempotent |
| `app/Services/RegistrationOtpService.php` | Konstanta TTL, hash OTP, rate limit |
| `app/Services/ChatInboxService.php` | Query inbox terfokus + eager load |
| `app/Services/Emergency/EmergencyReplacementCandidateService.php` | Eligibility + assert + scopes |

---

## 3. Pure calculator (ideal unit test)

**File:** `app/Services/Emergency/EmergencySettlementCalculator.php`

```php
final class EmergencySettlementCalculator
{
    /**
     * @return array{replacement_amount: float, retained_by_platform: float, snapshot: array<string, mixed>}
     */
    public function replacementPayoutOnCompletion(MuthowifBooking $booking, BookingPayment $payment): array
    {
        $totalDays = max(1, $booking->billingNightsInclusive());
        $replacementDays = max(0, $this->remainingServiceDays($booking));
        $pool = round(
            (float) $payment->muthowif_net_amount - (float) ($payment->referral_reward_amount ?? 0),
            2
        );

        $replacementAmount = round($pool * ($replacementDays / $totalDays), 2);

        return [
            'replacement_amount' => $replacementAmount,
            'retained_by_platform' => round($pool - $replacementAmount, 2),
            'snapshot' => [/* ... */],
        ];
    }
}
```

**Kenapa rapi**

- Single responsibility
- Return shape terdokumentasi (PHPDoc array shape)
- Tidak tulis DB / kirim notifikasi

**Agar lebih clean**

- Inject/`$now` untuk boundary hari agar test tidak bergantung wall clock

---

## 4. Form Request

**File:** `app/Http/Requests/AdminUpdateUserRequest.php`

```php
public function rules(): array
{
    return [
        'email' => ['required', 'email', Rule::unique(User::class)->ignore($user->id)],
        'role' => ['required', Rule::enum(UserRole::class)],
        'customer_type' => ['nullable', Rule::enum(CustomerType::class)],
        // ...
    ];
}

public function authorize(): bool
{
    return $this->user()?->isAdmin() ?? false;
}

protected function prepareForValidation(): void
{
    // normalisasi '' → null
}

public function withValidator(Validator $validator): void
{
    // invariant bisnis: no self-demotion, last-admin, customer_type wajib
}
```

**Kenapa rapi**

- Rules + authorize + normalisasi + invariant di satu tempat
- Pakai `Rule::enum`

**Agar lebih clean**

- Di `after`-hook, pakai enum typed (`$this->enum(...)`) daripada `tryFrom` string mentah

**Lainnya:** `app/Http/Requests/Auth/LoginRequest.php`, `app/Http/Requests/ProfileUpdateRequest.php`

---

## 5. Policy (satu aksi = satu method)

**File:** `app/Policies/MuthowifBookingPolicy.php`

```php
public function pay(User $user, MuthowifBooking $booking): bool
{
    return $user->isCustomer()
        && $booking->customer_id === $user->id
        && $booking->status === BookingStatus::Confirmed
        && $booking->payment_status === PaymentStatus::Pending;
}

public function complete(User $user, MuthowifBooking $booking): bool
{
    return $user->isCustomer()
        && $booking->customer_id === $user->id
        && ! $booking->isSupport()
        && $booking->status === BookingStatus::Confirmed
        && $booking->payment_status === PaymentStatus::Paid;
}
```

**Kenapa rapi**

- Nama method = use-case (`pay`, `invoice`, `cancelAsCustomer`, …)
- Helper privat `muthowifOwns()` / `bookingChatParticipant()`
- Emergency didelegasikan ke `BookingEmergencyReportPolicy`

**Agar lebih clean**

- Samakan `requestPostPayRefund` / `requestPostPayReschedule` dengan cek status/payment yang dipakai di tempat lain

**Lainnya:** `app/Policies/MuthowifServicePolicy.php`, `app/Policies/SupportTicketPolicy.php`, …

---

## 6. Enum + domain helper

**File:** `app/Enums/EmergencyReportStatus.php`

```php
enum EmergencyReportStatus: string
{
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Verified = 'verified';
    case Rejected = 'rejected';
    case Resolved = 'resolved';

    public function label(): string
    {
        return __('emergency.report_status.'.$this->value);
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Submitted, self::UnderReview, self::Verified], true);
    }
}
```

**Kenapa rapi**

- Tidak tersebar magic string
- Predicate domain di enum

**Agar lebih clean**

- Terapkan pola sama ke status payment di `BookingPayment` (masih string `settlement` / `capture`)

---

## 7. Model tipis + factory helper

**File:** `app/Models/BookingPayment.php`

```php
public function muthowifWalletCreditAmount(): float
{
    return round(
        (float) $this->muthowif_net_amount - (float) ($this->referral_reward_amount ?? 0),
        2
    );
}

/** @return array{id: string, order_id: string} */
public static function newPrimaryKeyAndOrderId(string $muthowifBookingId): array
{
    $id = (string) Str::uuid();

    return [
        'id' => $id,
        'order_id' => self::composeOrderId($muthowifBookingId, $id),
    ];
}
```

**Kenapa rapi**

- Cast + relasi + helper kecil
- Order ID deterministik, terdokumentasi
- Tanpa logic HTTP

**Agar lebih clean**

- Promosikan `status` ke backed enum (seperti `PaymentStatus` di booking)

**Model sejenis:** `app/Models/MuthowifBooking.php` (`isBookingChatOpen`, `canCompleteSupportWithCode`, `billingNightsInclusive`)

---

## 8. ViewModel (Blade data)

**File:** `app/ViewModels/Booking/BookingShowPageData.php`

```php
final class BookingShowPageData
{
    public function __construct(
        public readonly MuthowifBooking $booking,
        public readonly float $customerTotal,
        public readonly string $statusBadgeClass,
        // ... field siap pakai Blade
    ) {}

    public static function make(Request $request, MuthowifBooking $booking, /* ... */): self
    {
        $pricing = BookingPricingViewData::forCustomer($booking, $addonsById);
        // map → new self(...)
    }
}
```

**Kenapa rapi**

- Controller tidak menghitung harga / badge di tempat
- `readonly` DTO untuk view
- Folder sudah terbagi: `Auth/`, `Booking/`, `Dashboard/`, `Layanan/`, `Public/`

**Agar lebih clean**

- Kurangi `mixed` pada constructor; tipikan ke model/DTO/nullable konkret

---

## 9. Listener / Job tipis

**File:** `app/Listeners/NotifyAdminServiceMonitorOnBookingChange.php`

```php
final class NotifyAdminServiceMonitorOnBookingChange
{
    public function handle(CustomerBookingUpdated $event): void
    {
        AdminServiceMonitorBroadcast::notify($event->booking, 'booking_updated');
    }
}
```

**Kenapa rapi**

- Satu baris orkestrasi
- Detail di Support/Service, bukan di listener

**Target ukuran ideal:** Listener/Job ≤ ~30–50 baris; sisanya di Service.

---

## 10. Service dengan transaksi aman

**File:** `app/Services/AffiliateCommissionService.php`

```php
public function createPendingFromSettledPayment(BookingPayment $payment): ?AffiliateCommission
{
    return DB::transaction(function () use ($payment): ?AffiliateCommission {
        $lockedPayment = BookingPayment::query()
            ->whereKey($payment->getKey())
            ->lockForUpdate()
            ->firstOrFail();

        // guard → idempotent existing → create Pending
    });
}
```

Lifecycle terbaca: `createPendingFromSettledPayment` → `markAvailableOnCompletion` → `voidForBooking`.

**Agar lebih clean**

- Inject notifier di constructor, jangan `app(SomeClass::class)` di dalam transaksi

---

## Pola target (template clean)

```
Route
  → Form Request (rules + authorize)
  → Controller (1–3 baris orkestrasi)
       → authorize via Policy
       → Service / ViewModel
       → view / JSON Resource
```

Contoh controller ideal setelah refactor fat controller:

```php
public function store(StoreBookingRequest $request, BookingStoreService $service): RedirectResponse
{
    $this->authorize('create', MuthowifBooking::class);

    $booking = $service->create($request->user(), $request->validated());

    return redirect()->route('customer.bookings.show', $booking);
}
```

---

## Kontrast: yang perlu dipecah

| File | Masalah |
|------|---------|
| `app/Http/Controllers/Auth/RegisteredUserController.php` | Fat controller (~1000+ baris) |
| `app/Http/Controllers/Customer/BookingController.php` | Fat controller (~1000+ baris) |
| `app/Listeners/ProcessMootaWebhookForBookingPayments.php` | Fat listener / settlement monolith |
| `app/Http/Controllers/Api/Customer/BookingApiController.php` | API fat controller |

**Arah refactor (prioritas):**

1. Extrak Form Request per endpoint
2. Extrak Service per use-case (`RegisterCustomerService`, `CreateBookingService`, …)
3. Extrak Policy checks yang masih inline
4. Extrak ViewModel untuk data Blade yang kompleks
5. Listener/Job hanya memanggil service

---

## Checklist “apakah kode ini clean?”

- [ ] Controller < ~80 baris per class (atau jelas orkestrasi saja)
- [ ] Validasi di Form Request, bukan di controller
- [ ] Authorization di Policy, bukan `if` panjang di action
- [ ] Domain logic di Service / calculator murni
- [ ] Status/tipe pakai Enum, bukan magic string
- [ ] Model hanya relasi + cast + predicate kecil
- [ ] Blade dapat data dari ViewModel / Support, bukan kalkulasi di view
- [ ] Transaksi DB + `lockForUpdate` untuk money/wallet/commission
- [ ] Side-effect (WA, push, email) di Job/Notifier, bukan di tengah business method besar

---

## Ringkas: “golden samples” untuk ditiru dulu

1. `app/Http/Controllers/Admin/ServiceMonitorController.php` — thin controller  
2. `app/Services/BookingPricingService.php` — service domain  
3. `app/Services/Emergency/EmergencySettlementCalculator.php` — pure calculator  
4. `app/Http/Requests/AdminUpdateUserRequest.php` — form request  
5. `app/Policies/MuthowifBookingPolicy.php` — policy  
6. `app/Enums/EmergencyReportStatus.php` — enum  
7. `app/Models/BookingPayment.php` — model helper  
8. `app/ViewModels/Booking/BookingShowPageData.php` — view model  
9. `app/Listeners/NotifyAdminServiceMonitorOnBookingChange.php` — thin listener  
10. `app/Services/AffiliateCommissionService.php` — transactional service  

Gunakan 10 file di atas sebagai standar saat me-refactor `RegisteredUserController` / `BookingController`.
