<?php

namespace App\Services;

use App\Enums\MuthowifServiceType;
use App\Models\MuthowifBooking;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;

final class BookingDocumentStore
{
    public const TEMP_PREFIX = 'temp-booking-documents/';

    /** @var list<string> */
    public const FIELDS = [
        'ticket_outbound',
        'ticket_return',
        'passport',
        'itinerary',
        'visa',
    ];

    /**
     * @return array<string, list<mixed>>
     */
    public function validationRules(Request $request): array
    {
        $rules = [];

        foreach (self::FIELDS as $field) {
            $required = match ($field) {
                'ticket_outbound', 'ticket_return', 'passport' => true,
                'itinerary' => $request->input('service_type') === 'group',
                'visa' => false,
            };

            $rules[$field] = $this->rulesForField($request, $field, $required);
        }

        return $rules;
    }

    /**
     * @return list<mixed>
     */
    private function rulesForField(Request $request, string $field, bool $required): array
    {
        $hasPersistent = $this->hasPersistentFile($request, $field);

        $rules = [
            ($required && ! $hasPersistent) ? 'required' : 'nullable',
        ];

        if ($request->hasFile($field)) {
            $rules[] = function (string $attribute, mixed $value, \Closure $fail) use ($request, $field): void {
                $file = $request->file($field);
                if (! $file instanceof UploadedFile || ! $file->isValid()) {
                    $fail(__('bookings.validation.document_upload_failed'));
                }
            };
            $rules[] = File::types(['pdf', 'jpg', 'jpeg', 'png'])->max(10 * 1024);
        }

        if ($hasPersistent && ! $request->hasFile($field)) {
            $rules[] = function (string $attribute, mixed $value, \Closure $fail) use ($request, $field): void {
                if (! $this->isValidTempPath($request->input("temp_{$field}_path"))) {
                    $fail(__('bookings.validation.document_temp_missing'));
                }
            };
        }

        return $rules;
    }

    public function hasPersistentFile(Request $request, string $field): bool
    {
        if ($request->hasFile($field)) {
            $file = $request->file($field);

            return $file instanceof UploadedFile && $file->isValid();
        }

        return $this->isValidTempPath($request->input("temp_{$field}_path"));
    }

    public function isValidTempPath(mixed $path): bool
    {
        if (! is_string($path) || $path === '') {
            return false;
        }

        $normalized = str_replace('\\', '/', $path);
        if (! str_starts_with($normalized, self::TEMP_PREFIX) || str_contains($normalized, '..')) {
            return false;
        }

        return Storage::disk('local')->exists($normalized);
    }

    public function persistTempUploadsOnValidationFailure(Request $request): void
    {
        foreach (self::FIELDS as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                if (! $file instanceof UploadedFile || ! $file->isValid()) {
                    continue;
                }

                $path = $file->store('temp-booking-documents', 'local');
                session()->flash("temp_{$field}_path", $path);
                session()->flash("temp_{$field}_name", $file->getClientOriginalName());

                continue;
            }

            if ($this->isValidTempPath($request->input("temp_{$field}_path"))) {
                session()->flash("temp_{$field}_path", str_replace('\\', '/', (string) $request->input("temp_{$field}_path")));
                session()->flash("temp_{$field}_name", $request->input("temp_{$field}_name"));
            }
        }
    }

    public function moveToBookingDirectory(Request $request, string $field, string $targetDir): ?string
    {
        if ($request->hasFile($field)) {
            $file = $request->file($field);
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                throw ValidationException::withMessages([
                    $field => [__('bookings.validation.document_upload_failed')],
                ]);
            }

            $stored = $file->store($targetDir, 'local');
            if ($stored === false || $stored === '') {
                throw ValidationException::withMessages([
                    $field => [__('bookings.validation.document_store_failed')],
                ]);
            }

            return $stored;
        }

        $tempPath = $request->input("temp_{$field}_path");
        if (! $this->isValidTempPath($tempPath)) {
            return null;
        }

        $normalized = str_replace('\\', '/', (string) $tempPath);
        $newPath = rtrim($targetDir, '/').'/'.basename($normalized);

        if (! Storage::disk('local')->move($normalized, $newPath)) {
            throw ValidationException::withMessages([
                $field => [__('bookings.validation.document_temp_missing')],
            ]);
        }

        return $newPath;
    }

    /**
     * @throws ValidationException
     */
    public function assertRequiredDocumentsStored(MuthowifBooking $booking, MuthowifServiceType $serviceType): void
    {
        $required = [
            'ticket_outbound' => 'ticket_outbound_path',
            'ticket_return' => 'ticket_return_path',
            'passport' => 'passport_path',
        ];

        if ($serviceType === MuthowifServiceType::Group) {
            $required['itinerary'] = 'itinerary_path';
        }

        $missing = [];
        foreach ($required as $field => $column) {
            if (! filled($booking->{$column})) {
                $missing[$field] = [__('bookings.validation.document_required')];
            }
        }

        if ($missing !== []) {
            throw ValidationException::withMessages($missing);
        }
    }
}
