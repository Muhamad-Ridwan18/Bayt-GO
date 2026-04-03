<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\MuthowifVerificationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Carbon\CarbonInterface;
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
    ];

    protected static function booted(): void
    {
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

    public function blockedDates(): HasMany
    {
        return $this->hasMany(MuthowifBlockedDate::class)->orderBy('blocked_on');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(MuthowifBooking::class);
    }

    /**
     * Apakah slot [starts_on, ends_on] masih bisa dipesan (tidak bentrok libur / booking blocking).
     */
    public function isSlotAvailableForRange(CarbonInterface $start, CarbonInterface $end): bool
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

        return ! $this->bookings()
            ->whereIn('status', $blocking)
            ->where('starts_on', '<=', $endStr)
            ->where('ends_on', '>=', $startStr)
            ->exists();
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
}
