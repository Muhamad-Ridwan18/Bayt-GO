<?php

namespace App\Http\Controllers;

use App\Enums\MuthowifVerificationStatus;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\MuthowifProfile;
use App\Services\MuthowifReferralCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $request->user()->load(['muthowifProfile.referredBy.user']);

        return view('profile.edit', [
            'user' => $request->user(),
            'muthowifProfile' => $request->user()->muthowifProfile,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function updatePublicProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $profile = $user->muthowifProfile;
        abort_unless($profile !== null, 403);

        $validated = $request->validate([
            'phone' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:2000'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'passport_number' => ['nullable', 'string', 'max:64'],
            'languages' => ['nullable', 'array', 'max:30'],
            'languages.*' => ['nullable', 'string', 'max:120'],
            'educations' => ['nullable', 'array', 'max:30'],
            'educations.*' => ['nullable', 'string', 'max:180'],
            'work_experiences' => ['nullable', 'array', 'max:30'],
            'work_experiences.*' => ['nullable', 'string', 'max:180'],
            'reference_text' => ['nullable', 'string', 'max:3000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'inviter_referral_code' => ['nullable', 'string', 'max:16'],
        ]);

        $profile->phone = $validated['phone'] ?? null;
        $profile->address = $validated['address'] ?? null;
        $profile->birth_date = $validated['birth_date'] ?? null;
        $profile->passport_number = $validated['passport_number'] ?? null;
        $profile->languages = $this->normalizeStringList($validated['languages'] ?? []);
        $profile->educations = $this->normalizeStringList($validated['educations'] ?? []);
        $profile->work_experiences = $this->normalizeStringList($validated['work_experiences'] ?? []);
        $profile->reference_text = $validated['reference_text'] ?? null;

        if ($request->hasFile('photo')) {
            $disk = Storage::disk('local');
            if (is_string($profile->photo_path) && $profile->photo_path !== '' && $disk->exists($profile->photo_path)) {
                $disk->delete($profile->photo_path);
            }
            $profile->photo_path = $request->file('photo')->store('muthowif/photos', 'local');
        }

        if ($profile->referred_by_muthowif_profile_id === null) {
            $codeRaw = $validated['inviter_referral_code'] ?? null;
            $code = is_string($codeRaw) ? strtoupper(trim($codeRaw)) : '';
            if ($code !== '') {
                /** @var MuthowifProfile|null $inviter */
                $inviter = MuthowifProfile::query()
                    ->where('referral_code', $code)
                    ->where('verification_status', MuthowifVerificationStatus::Approved)
                    ->first();
                if ($inviter === null) {
                    throw ValidationException::withMessages([
                        'inviter_referral_code' => [__('auth_custom.muthowif_referral_invalid')],
                    ]);
                }
                if ((string) $inviter->getKey() === (string) $profile->getKey()) {
                    throw ValidationException::withMessages([
                        'inviter_referral_code' => [__('profile_public.referral_self_error')],
                    ]);
                }
                $profile->referred_by_muthowif_profile_id = (string) $inviter->getKey();
            }
        }

        $profile->save();

        $fresh = $profile->fresh();
        if ($fresh !== null && $fresh->isApproved()) {
            app(MuthowifReferralCodeService::class)->ensureAssigned($fresh);
        }

        return Redirect::route('profile.edit')->with('status', 'public-profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * @param  array<int, mixed>  $rows
     * @return array<int, string>
     */
    private function normalizeStringList(array $rows): array
    {
        return array_values(array_filter(array_map(
            static fn ($item): string => trim((string) $item),
            $rows
        ), static fn (string $item): bool => $item !== ''));
    }
}
