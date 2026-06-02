<?php

namespace App\Models;

use App\Enums\BookingIncidentCaseType;
use App\Enums\BookingIncidentResolution;
use App\Enums\BookingIncidentSeverity;
use App\Enums\BookingIncidentStatus;
use App\Enums\BookingReplacementStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookingIncident extends Model
{
    use HasUuids;

    protected $fillable = [
        'muthowif_booking_id',
        'case_type',
        'severity',
        'status',
        'resolution_type',
        'reported_by_user_id',
        'assigned_admin_id',
        'customer_statement',
        'muthowif_statement',
        'admin_resolution_note',
        'metadata',
        'policy_version',
        'completed_service_days',
        'total_service_days',
        'replacement_recruitment_open',
        'replacement_recruitment_opened_at',
        'customer_choice_opened_at',
        'opened_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'case_type' => BookingIncidentCaseType::class,
            'severity' => BookingIncidentSeverity::class,
            'status' => BookingIncidentStatus::class,
            'resolution_type' => BookingIncidentResolution::class,
            'metadata' => 'array',
            'replacement_recruitment_open' => 'boolean',
            'replacement_recruitment_opened_at' => 'datetime',
            'customer_choice_opened_at' => 'datetime',
            'opened_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function isCustomerChoiceOpen(): bool
    {
        return $this->customer_choice_opened_at !== null;
    }

    /**
     * Kandidat yang boleh dipilih jamaah.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<BookingReplacement, $this>
     */
    public function selectableReplacements(): HasMany
    {
        return $this->replacements()
            ->whereIn('status', array_map(
                fn (BookingReplacementStatus $s) => $s->value,
                BookingReplacementStatus::customerSelectable()
            ))
            ->orderBy('admin_approved_at')
            ->orderBy('volunteered_at');
    }

    public function muthowifBooking(): BelongsTo
    {
        return $this->belongsTo(MuthowifBooking::class);
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }

    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_admin_id');
    }

    /**
     * @return HasMany<BookingIncidentEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(BookingIncidentEvent::class)->orderBy('created_at');
    }

    /**
     * @return HasMany<BookingReplacement, $this>
     */
    public function replacements(): HasMany
    {
        return $this->hasMany(BookingReplacement::class);
    }

    public function isOpen(): bool
    {
        return $this->status->isOpen();
    }
}
