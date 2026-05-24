<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\MuthowifVerificationStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

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
                    ? \Illuminate\Support\Str::slug($profile->user->name)
                    : \Illuminate\Support\Str::slug((string) $profile->uuid);
            } else {
                $baseSlug = \Illuminate\Support\Str::slug($profile->slug);
            }

            $profile->slug = static::generateUniqueSlug($baseSlug);
        });

        static::deleting(function (MuthowifProfile $profile): void {
            $disk = Storage::disk('local');
            foreach ($profile->supportingDocuments as $doc) {
                if ($doc->path) {
                    $disk->delete($doc->path);
                }
            }
            $profile->supportingDocuments()->delete();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeApproved($query)
    {
        return $query->where('verification_status', MuthowifVerificationStatus::Approved);
    }

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'verified_at' => 'datetime',
            'verification_status' => MuthowifVerificationStatus::class,
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
     * @param  string       $base      The desired base slug (already slugified).
     * @param  string|null  $excludeId UUID of the record to exclude (useful for updates).
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

            $slug = $base . '-' . $index;
            $index++;
        }
    }
}
