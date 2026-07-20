<?php

namespace App\ViewModels\Auth;

final class RegisterPageData
{
    /**
     * @param  array{original_name: string, label: string}|null  $cachedPhoto
     * @param  array{original_name: string, label: string}|null  $cachedKtp
     * @param  list<array{id: string, path: string, original_name: string, remove: array{type: string, file_id: string, path: string}}>  $cachedSupportingDocuments
     */
    public function __construct(
        public readonly bool $otpEnabled,
        public readonly string $selectedRole,
        public readonly string $customerType,
        public readonly ?array $cachedPhoto,
        public readonly ?array $cachedKtp,
        public readonly array $cachedSupportingDocuments,
        public readonly string $removeFileUrl,
    ) {}

    public static function make(bool $otpEnabled): self
    {
        $files = session('registration_files', []);
        $photo = is_array($files['photo'] ?? null) ? $files['photo'] : null;
        $ktp = is_array($files['ktp_image'] ?? null) ? $files['ktp_image'] : null;
        $docs = is_array($files['supporting_documents'] ?? null) ? $files['supporting_documents'] : [];

        $supporting = [];
        foreach ($docs as $doc) {
            if (! is_array($doc)) {
                continue;
            }

            $id = (string) ($doc['id'] ?? '');
            $path = (string) ($doc['path'] ?? '');
            $name = (string) ($doc['original_name'] ?? '');

            if ($name === '') {
                continue;
            }

            $supporting[] = [
                'id' => $id,
                'path' => $path,
                'original_name' => $name,
                'remove' => [
                    'type' => 'supporting_document',
                    'file_id' => $id,
                    'path' => $path,
                ],
            ];
        }

        return new self(
            otpEnabled: $otpEnabled,
            selectedRole: (string) old('role', 'customer'),
            customerType: (string) old('customer_type', 'personal'),
            cachedPhoto: $photo ? [
                'original_name' => (string) ($photo['original_name'] ?? ''),
                'label' => __('guest.register.uploaded_file', [
                    'name' => (string) ($photo['original_name'] ?? ''),
                ]),
            ] : null,
            cachedKtp: $ktp ? [
                'original_name' => (string) ($ktp['original_name'] ?? ''),
                'label' => __('guest.register.uploaded_file', [
                    'name' => (string) ($ktp['original_name'] ?? ''),
                ]),
            ] : null,
            cachedSupportingDocuments: $supporting,
            removeFileUrl: route('register.remove-file'),
        );
    }

    public function isCustomer(): bool
    {
        return $this->selectedRole === 'customer';
    }

    public function isMuthowif(): bool
    {
        return $this->selectedRole === 'muthowif';
    }

    public function isCompany(): bool
    {
        return $this->customerType === 'company';
    }

    public function hasCachedSupportingDocuments(): bool
    {
        return $this->cachedSupportingDocuments !== [];
    }

    /**
     * @return array{selectedRole: string, customerType: string, removeFileUrl: string}
     */
    public function alpineConfig(): array
    {
        return [
            'selectedRole' => $this->selectedRole,
            'customerType' => $this->customerType,
            'removeFileUrl' => $this->removeFileUrl,
        ];
    }
}
