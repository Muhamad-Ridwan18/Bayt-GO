<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MuthowifSupportingDocument extends Model
{
    use HasUuids;

    protected $fillable = [
        'muthowif_profile_id',
        'path',
        'original_name',
        'sort_order',
    ];

    public function muthowifProfile(): BelongsTo
    {
        return $this->belongsTo(MuthowifProfile::class);
    }

    public function displayName(): string
    {
        $name = trim((string) ($this->original_name ?? ''));

        return $name !== '' ? $name : basename((string) $this->path);
    }

    public function isPdf(): bool
    {
        return str_ends_with(strtolower($this->displayName()), '.pdf')
            || str_ends_with(strtolower((string) $this->path), '.pdf');
    }

    public function isImage(): bool
    {
        $name = strtolower($this->displayName());
        $path = strtolower((string) $this->path);

        foreach (['.jpg', '.jpeg', '.png', '.webp', '.gif'] as $ext) {
            if (str_ends_with($name, $ext) || str_ends_with($path, $ext)) {
                return true;
            }
        }

        return false;
    }

    public function previewKind(): string
    {
        if ($this->isPdf()) {
            return 'pdf';
        }

        if ($this->isImage()) {
            return 'image';
        }

        return 'other';
    }

    public function publicUrl(?MuthowifProfile $profile = null): string
    {
        $profile ??= $this->muthowifProfile;

        return route('layanan.document', [
            'publicProfile' => $profile,
            'document' => $this,
        ]);
    }
}
