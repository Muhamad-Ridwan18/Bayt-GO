<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\MuthowifAccountStatus;
use App\Enums\MuthowifVerificationStatus;
use App\Support\IntlPhone;
use App\Support\MarketplaceProfileCache;
use App\Support\PublicMarketplaceMedia;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Kolom JSON `languages`, `educations`, `work_experiences` disimpan sebagai array of string (satu item = satu baris input).
 * Di tampilan gunakan `languagesForDisplay()` dll. atau komponen `<x-line-list>` agar aman jika null.
 */
class MuthowifProfile extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'phone',
        'address',
        'nik',
        'birth_date',
        'passport_number',
        'languages',
        'educations',
        'work_experiences',
        'reference_text',
        'photo_path',
        'ktp_image_path',
        'verification_status',
        'account_status',
        'verified_at',
        'rejection_reason',
        'wallet_balance',
        'referral_code',
        'referred_by_muthowif_profile_id',
        'slug',
    ];

    protected static function booted(): void
    {
        static::creating(function (MuthowifProfile $profile): void {
            if ($profile->slug === null) {
                $baseSlug = $profile->user?->name
                    ? Str::slug($profile->user->name)
                    : Str::slug((string) $profile->uuid);
            } else {
                $baseSlug = Str::slug($profile->slug);
            }

            $profile->slug = static::generateUniqueSlug($baseSlug);
        });

        static::deleting(function (MuthowifProfile $profile): void {
            PublicMarketplaceMedia::removeProfilePhoto($profile);

            $disk = Storage::disk('local');
            foreach ($profile->supportingDocuments as $doc) {
                if ($doc->path) {
                    $disk->delete($doc->path);
                }
            }
            $profile->supportingDocuments()->delete();
        });

        static::saved(function (MuthowifProfile $profile): void {
            MarketplaceProfileCache::forget($profile);

            if ($profile->wasChanged('photo_path') || $profile->wasChanged('verification_status') || $profile->wasChanged('account_status')) {
                PublicMarketplaceMedia::syncProfilePhoto($profile);
            }
        });
    }

    public function photoUrl(): string
    {
        if (! filled($this->photo_path)) {
            return route('layanan.photo', $this);
        }

        if (self::photoPathIsExternalUrl($this->photo_path)) {
            return $this->photo_path;
        }

        return PublicMarketplaceMedia::profilePhotoUrl($this)
            ?? route('layanan.photo', $this);
    }

    public static function photoPathIsExternalUrl(?string $path): bool
    {
        return is_string($path)
            && $path !== ''
            && (str_starts_with($path, 'http://') || str_starts_with($path, 'https://'));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeApproved($query)
    {
        return $query
            ->where('verification_status', MuthowifVerificationStatus::Approved)
            ->where(function ($q): void {
                $q->whereNull('account_status')
                    ->orWhere('account_status', MuthowifAccountStatus::Active->value);
            });
    }

    /** Hanya profil yang sudah mengisi minimal satu layanan (paket). */
    public function scopeHasPublishedServices($query)
    {
        return $query->whereHas('services');
    }

    /**
     * Statistik untuk kartu marketplace (rating & jumlah ulasan).
     *
     * @param  \Illuminate\Database\Eloquent\Builder<MuthowifProfile>  $query
     * @return \Illuminate\Database\Eloquent\Builder<MuthowifProfile>
     */
    public function scopeWithMarketplaceStats($query)
    {
        return $query
            ->withCount([
                'bookings as confirmed_bookings_count' => static fn ($q) => $q->where('status', BookingStatus::Confirmed),
                'bookingReviews',
            ])
            ->withAvg('bookingReviews as average_rating', 'rating');
    }

    /**
     * Urutan: muthowif berulasan dulu (rating tertinggi), sisanya by verified_at.
     * Wajib dipanggil setelah {@see scopeWithMarketplaceStats}.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<MuthowifProfile>  $query
     * @return \Illuminate\Database\Eloquent\Builder<MuthowifProfile>
     */
    public function scopeOrderByMarketplaceRanking($query)
    {
        return $query
            ->orderByRaw('CASE WHEN COALESCE(booking_reviews_count, 0) > 0 THEN 1 ELSE 0 END DESC')
            ->orderByDesc('average_rating')
            ->orderByDesc('verified_at');
    }

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'verified_at' => 'datetime',
            'verification_status' => MuthowifVerificationStatus::class,
            'account_status' => MuthowifAccountStatus::class,
            'languages' => 'array',
            'educations' => 'array',
            'work_experiences' => 'array',
            'wallet_balance' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Cari profil muthowif berdasarkan nomor WhatsApp (beberapa format penyimpanan).
     */
    public static function findByPhone(string $normalized, string $phoneInput): ?self
    {
        $direct = static::query()
            ->whereIn('phone', IntlPhone::storageLookupVariants($normalized, $phoneInput))
            ->first();
        if ($direct) {
            return $direct;
        }

        $suffix = substr($normalized, -9);
        if ($suffix === false || $suffix === '') {
            return null;
        }

        $candidates = static::query()
            ->where('phone', 'like', '%'.$suffix)
            ->limit(50)
            ->get(['id', 'phone', 'user_id']);

        foreach ($candidates as $candidate) {
            if (IntlPhone::normalize($candidate->phone) === $normalized) {
                return $candidate;
            }
        }

        return null;
    }

    public function supportingDocuments(): HasMany
    {
        return $this->hasMany(MuthowifSupportingDocument::class)->orderBy('sort_order');
    }

    public function services(): HasMany
    {
        return $this->hasMany(MuthowifService::class)->orderBy('type');
    }

    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(MuthowifProfile::class, 'referred_by_muthowif_profile_id');
    }

    /**
     * @return HasMany<MuthowifProfile, $this>
     */
    public function referredMuthowifs(): HasMany
    {
        return $this->hasMany(MuthowifProfile::class, 'referred_by_muthowif_profile_id');
    }

    public function blockedDates(): HasMany
    {
        return $this->hasMany(MuthowifBlockedDate::class)->orderBy('blocked_on');
    }

    public function portfolios(): HasMany
    {
        return $this->hasMany(MuthowifPortfolio::class)->orderBy('sort_order')->orderBy('created_at', 'desc');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(MuthowifBooking::class);
    }

    public function bookingReviews(): HasMany
    {
        return $this->hasMany(BookingReview::class, 'muthowif_profile_id');
    }

    /**
     * Apakah jadwal [starts_on, ends_on] masih bisa dipesan (tidak bentrok libur / booking blocking).
     */
    public function isJadwalAvailableForRange(CarbonInterface $start, CarbonInterface $end, ?string $exceptBookingId = null): bool
    {
        if (! $this->isApproved()) {
            return false;
        }

        $startStr = $start->copy()->startOfDay()->toDateString();
        $endStr = $end->copy()->startOfDay()->toDateString();

        $blocked = $this->blockedDates()
            ->whereBetween('blocked_on', [$startStr, $endStr])
            ->exists();
        if ($blocked) {
            return false;
        }

        $blocking = array_map(
            static fn (BookingStatus $s) => $s->value,
            BookingStatus::blocksAvailability()
        );

        $query = $this->bookings()
            ->whereIn('status', $blocking)
            ->where('starts_on', '<=', $endStr)
            ->where('ends_on', '>=', $startStr);

        if ($exceptBookingId !== null && $exceptBookingId !== '') {
            $query->whereKeyNot($exceptBookingId);
        }

        return ! $query->exists();
    }

    public function isPending(): bool
    {
        return $this->verification_status === MuthowifVerificationStatus::Pending;
    }

    public function isApproved(): bool
    {
        return $this->verification_status === MuthowifVerificationStatus::Approved;
    }

    public function isActiveAccount(): bool
    {
        $status = $this->account_status ?? MuthowifAccountStatus::Active;

        return $status === MuthowifAccountStatus::Active;
    }

    public function isEligibleForEmergencyReplacement(): bool
    {
        return $this->isApproved() && $this->isActiveAccount();
    }

    public function isRejected(): bool
    {
        return $this->verification_status === MuthowifVerificationStatus::Rejected;
    }

    /**
     * @return list<string>
     */
    public function languagesForDisplay(): array
    {
        return $this->normalizeStringList($this->languages);
    }

    /**
     * @return list<string>
     */
    public function educationsForDisplay(): array
    {
        return $this->normalizeStringList($this->educations);
    }

    /**
     * @return list<string>
     */
    public function workExperiencesForDisplay(): array
    {
        return $this->normalizeStringList($this->work_experiences);
    }

    /**
     * @param  array<int, string>|null  $value
     * @return list<string>
     */
    private function normalizeStringList(?array $value): array
    {
        if ($value === null) {
            return [];
        }

        return array_values(array_filter(
            $value,
            fn (mixed $s): bool => is_string($s) && trim($s) !== ''
        ));
    }

    /**
     * Generate a unique slug based on $base.
     * If $base already exists in the table, append -1, -2, … until it is unique.
     *
     * @param  string  $base  The desired base slug (already slugified).
     * @param  string|null  $excludeId  UUID of the record to exclude (useful for updates).
     */
    public static function generateUniqueSlug(string $base, ?string $excludeId = null): string
    {
        $slug = $base;
        $index = 1;

        while (true) {
            $query = static::where('slug', $slug);

            if ($excludeId !== null) {
                $query->where('id', '!=', $excludeId);
            }

            if (! $query->exists()) {
                return $slug;
            }

            $slug = $base.'-'.$index;
            $index++;
        }
    }
}
