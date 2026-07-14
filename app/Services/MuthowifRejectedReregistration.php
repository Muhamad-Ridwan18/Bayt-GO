<?php

namespace App\Services;

use App\Enums\MuthowifVerificationStatus;
use App\Enums\UserRole;
use App\Models\MuthowifProfile;
use App\Models\User;
use App\Support\PublicMarketplaceMedia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class MuthowifRejectedReregistration
{
    public function findByEmail(string $email): ?User
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return null;
        }

        return User::query()
            ->where('email', $email)
            ->where('role', UserRole::Muthowif)
            ->whereHas('muthowifProfile', function ($query): void {
                $query->where('verification_status', MuthowifVerificationStatus::Rejected);
            })
            ->with(['muthowifProfile.supportingDocuments'])
            ->first();
    }

    public function emailUniqueRule(mixed $role, ?string $email): Unique
    {
        $rule = Rule::unique(User::class, 'email');

        if ((string) $role !== UserRole::Muthowif->value || ! is_string($email) || trim($email) === '') {
            return $rule;
        }

        $existing = $this->findByEmail($email);
        if ($existing !== null) {
            $rule = $rule->ignore($existing->id);
        }

        return $rule;
    }

    public function discardStoredDocuments(MuthowifProfile $profile): void
    {
        PublicMarketplaceMedia::removeProfilePhoto($profile);

        $disk = Storage::disk('local');

        $profile->loadMissing('supportingDocuments');
        foreach ($profile->supportingDocuments as $doc) {
            if (filled($doc->path) && $disk->exists($doc->path)) {
                $disk->delete($doc->path);
            }
        }
        $profile->supportingDocuments()->delete();

        foreach ([$profile->photo_path, $profile->ktp_image_path] as $path) {
            if (! filled($path) || MuthowifProfile::photoPathIsExternalUrl((string) $path)) {
                continue;
            }
            if ($disk->exists($path)) {
                $disk->delete($path);
            }
        }

        $userId = (string) $profile->user_id;
        if ($userId !== '') {
            $disk->deleteDirectory('muthowif_documents/'.$userId);
        }
    }
}
