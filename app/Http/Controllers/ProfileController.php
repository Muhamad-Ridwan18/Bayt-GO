<?php

namespace App\Http\Controllers;

use App\Enums\MuthowifVerificationStatus;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\MuthowifProfile;
use App\Models\MuthowifSupportingDocument;
use App\Models\User;
use App\Services\MuthowifReferralCodeService;
use App\Services\UploadedImageOptimizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $request->user()->load(['muthowifProfile.referredBy.user', 'muthowifProfile.supportingDocuments']);

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
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
            'phone' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:2000'],
            'current_domicile_address' => ['nullable', 'string', 'max:2000'],
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
            'ktp_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'supporting_documents' => ['nullable', 'array', 'max:20'],
            'supporting_documents.*' => ['file', 'mimes:pdf,jpeg,jpg,png,webp', 'max:10240'],
            'delete_supporting_documents' => ['nullable', 'array', 'max:20'],
            'delete_supporting_documents.*' => ['string'],
            'inviter_referral_code' => ['nullable', 'string', 'max:16'],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }
        $user->save();

        $profile->phone = $validated['phone'] ?? null;
        $profile->address = $validated['address'] ?? null;
        $profile->current_domicile_address = $validated['current_domicile_address'] ?? null;
        $profile->birth_date = $validated['birth_date'] ?? null;
        $profile->passport_number = $validated['passport_number'] ?? null;
        $profile->languages = $this->normalizeStringList($validated['languages'] ?? []);
        $profile->educations = $this->normalizeStringList($validated['educations'] ?? []);
        $profile->work_experiences = $this->normalizeStringList($validated['work_experiences'] ?? []);
        $profile->reference_text = $validated['reference_text'] ?? null;

        if ($request->hasFile('photo')) {
            $this->deleteStoredPath($profile->photo_path);
            $profile->photo_path = app(UploadedImageOptimizer::class)->store(
                $request->file('photo'),
                $this->muthowifStorageDirectory($user),
                'local',
                'profile',
            );
        }

        if ($request->hasFile('ktp_image')) {
            $this->deleteStoredPath($profile->ktp_image_path);
            $profile->ktp_image_path = app(UploadedImageOptimizer::class)->store(
                $request->file('ktp_image'),
                $this->muthowifStorageDirectory($user),
                'local',
                'profile',
            );
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

        $deleteDocumentIds = array_values(array_filter(
            $validated['delete_supporting_documents'] ?? [],
            static fn (mixed $id): bool => is_string($id) && $id !== ''
        ));
        if ($deleteDocumentIds !== []) {
            $documents = $profile->supportingDocuments()->whereKey($deleteDocumentIds)->get();
            foreach ($documents as $document) {
                $this->deleteStoredPath($document->path);
                $document->delete();
            }
        }

        $files = $request->file('supporting_documents', []);
        if (! is_array($files)) {
            $files = array_filter([$files]);
        }

        $nextSortOrder = (int) $profile->supportingDocuments()->max('sort_order') + 1;
        foreach ($files as $file) {
            if ($file && $file->isValid()) {
                $profile->supportingDocuments()->create([
                    'path' => app(UploadedImageOptimizer::class)->store(
                        $file,
                        $this->muthowifStorageDirectory($user),
                        'local',
                        'document',
                    ),
                    'original_name' => $file->getClientOriginalName(),
                    'sort_order' => $nextSortOrder++,
                ]);
            }
        }

        $fresh = $profile->fresh();
        if ($fresh !== null && $fresh->isApproved()) {
            app(MuthowifReferralCodeService::class)->ensureAssigned($fresh);
        }

        return Redirect::route('profile.edit')->with('status', 'public-profile-updated');
    }

    public function publicPhoto(Request $request): Response
    {
        $profile = $request->user()->muthowifProfile;
        abort_unless($profile !== null, 404);

        return $this->storedFileResponse($profile->photo_path);
    }

    public function publicKtp(Request $request): Response
    {
        $profile = $request->user()->muthowifProfile;
        abort_unless($profile !== null, 404);

        return $this->storedFileResponse($profile->ktp_image_path);
    }

    public function publicSupportingDocument(Request $request, MuthowifSupportingDocument $document): Response
    {
        $profile = $request->user()->muthowifProfile;
        abort_unless($profile !== null && $document->muthowif_profile_id === $profile->id, 404);

        return $this->storedFileResponse($document->path, $document->original_name ?? basename($document->path));
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

    private function muthowifStorageDirectory(User $user): string
    {
        return 'muthowif_documents/'.$user->id;
    }

    private function storedFileResponse(?string $path, ?string $name = null): Response
    {
        if (! is_string($path) || $path === '') {
            abort(404);
        }

        foreach (['local', 'public'] as $diskName) {
            $disk = Storage::disk($diskName);
            if ($disk->exists($path)) {
                return $disk->response($path, $name);
            }
        }

        abort(404);
    }

    private function deleteStoredPath(?string $path): void
    {
        if (! is_string($path) || $path === '') {
            return;
        }

        foreach (['local', 'public'] as $diskName) {
            $disk = Storage::disk($diskName);
            if ($disk->exists($path)) {
                $disk->delete($path);
            }
        }
    }
}
