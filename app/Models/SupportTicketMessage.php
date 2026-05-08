<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'support_ticket_id',
    'user_id',
    'body',
    'attachments',
    'is_staff',
])]
class SupportTicketMessage extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'is_staff' => 'boolean',
            'attachments' => 'array',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return list<array{url: string, original_name: string, mime: string, is_image: bool}>
     */
    public function attachmentUrls(): array
    {
        $list = $this->attachments ?? [];
        if (! is_array($list)) {
            return [];
        }

        $out = [];

        foreach ($list as $row) {
            if (! is_array($row) || ! isset($row['path']) || ! is_string($row['path'])) {
                continue;
            }
            $path = trim($row['path']);
            if ($path === '' || ! Storage::disk('public')->exists($path)) {
                continue;
            }

            $mime = is_string($row['mime'] ?? null) ? $row['mime'] : '';
            $name = is_string($row['original_name'] ?? null) ? $row['original_name'] : basename($path);

            $detected = Storage::disk('public')->mimeType($path);
            $effectiveMime = $mime !== ''
                ? $mime
                : (is_string($detected) ? $detected : 'application/octet-stream');

            $out[] = [
                'url' => Storage::disk('public')->url($path),
                'original_name' => $name,
                'mime' => $effectiveMime,
                'is_image' => str_starts_with($effectiveMime, 'image/'),
            ];
        }

        return $out;
    }
}
