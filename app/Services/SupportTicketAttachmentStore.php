<?php

namespace App\Services;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

final class SupportTicketAttachmentStore
{
    public const MAX_FILES = 5;

    public const MAX_KB = 5120;

    /**
     * @return array<string, mixed>
     */
    public static function validationRules(): array
    {
        return [
            'attachments' => ['sometimes', 'nullable', 'array', 'max:'.self::MAX_FILES],
            'attachments.*' => ['file', 'max:'.self::MAX_KB, 'mimetypes:image/jpeg,image/png,image/gif,image/webp,application/pdf'],
        ];
    }

    /**
     * @return list<array{path: string, original_name: string, mime: string}>
     */
    public static function storeFromRequest(Request $request, SupportTicket $ticket, SupportTicketMessage $message): array
    {
        if (! $request->hasFile('attachments')) {
            return [];
        }

        $uploaded = $request->file('attachments', []);

        /** @var list<UploadedFile> $files */
        $files = is_array($uploaded) ? $uploaded : [$uploaded];
        /** @var list<UploadedFile> $validFiles */
        $validFiles = array_values(array_filter(
            array_slice($files, 0, self::MAX_FILES),
            static fn ($f): bool => $f instanceof UploadedFile && $f->isValid(),
        ));

        $directory = sprintf('support-tickets/%s/%s', $ticket->getKey(), $message->getKey());

        $out = [];

        foreach ($validFiles as $file) {
            $path = app(UploadedImageOptimizer::class)->store($file, $directory, 'public', 'attachment');
            $out[] = [
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType() !== false && $file->getMimeType() !== ''
                    ? (string) $file->getMimeType()
                    : 'application/octet-stream',
            ];
        }

        return $out;
    }
}
