<?php

namespace App\ViewModels\Auth;

use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

final class RegisterVerifyWhatsAppPageData
{
    /**
     * @param  list<string>  $bannerErrors
     */
    public function __construct(
        public readonly string $maskedPhone,
        public readonly string $pendingPhone,
        public readonly ?string $pendingCountry,
        public readonly string $role,
        public readonly bool $phoneVerifiedInitial,
        public readonly ?string $status,
        public readonly array $bannerErrors,
    ) {}

    public static function make(
        string $maskedPhone,
        string $pendingPhone,
        ?string $pendingCountry,
        string $role,
        bool $phoneVerifiedInitial,
        ?string $status,
        ViewErrorBag|MessageBag $errors,
    ): self {
        $messages = $errors instanceof ViewErrorBag
            ? $errors->getMessages()
            : $errors->messages();

        $bannerErrors = collect($messages)
            ->except(['phone', 'country'])
            ->flatten()
            ->values()
            ->all();

        return new self(
            maskedPhone: $maskedPhone,
            pendingPhone: $pendingPhone,
            pendingCountry: $pendingCountry,
            role: $role,
            phoneVerifiedInitial: $phoneVerifiedInitial,
            status: $status,
            bannerErrors: $bannerErrors,
        );
    }

    public function hasBannerErrors(): bool
    {
        return $this->bannerErrors !== [];
    }

    public function hasStatus(): bool
    {
        return filled($this->status);
    }
}
