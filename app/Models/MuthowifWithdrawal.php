<?php

namespace App\Models;

use App\Models\MuthowifProfile;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MuthowifWithdrawal extends Model
{
    use HasUuids;

    protected $fillable = [
        'muthowif_profile_id',
        'amount',
        'beneficiary_name',
        'beneficiary_account',
        'beneficiary_bank',
        'notes',
        'status',
        'midtrans_reference_no',
        'midtrans_initial_status',
        'requested_at',
        'approved_at',
        'processing_at',
        'completed_at',
        'failed_at',
        'failed_reason',
        'transfer_proof_path',
        'midtrans_notification_payload',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'requested_at' => 'datetime',
            'approved_at' => 'datetime',
            'processing_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
            'midtrans_notification_payload' => 'array',
        ];
    }

    public function muthowifProfile(): BelongsTo
    {
        return $this->belongsTo(MuthowifProfile::class);
    }
}

