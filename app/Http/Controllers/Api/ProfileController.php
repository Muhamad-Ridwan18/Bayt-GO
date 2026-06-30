<?php

namespace App\Http\Controllers\Api;

use App\Enums\MuthowifVerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\MuthowifProfile;
use App\Services\MuthowifReferralCodeService;
use App\Services\UploadedImageOptimizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $muthowif = $user->muthowifProfile;

        if ($muthowif) {
            $muthowif->load(['supportingDocuments', 'referredBy.user']);
        }

        return response()->json([
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
            'muthowif' => $muthowif ? [
                'phone' => $muthowif->phone,
                'passport_number' => $muthowif->passport_number,
                'birth_date' => $muthowif->birth_date ? $muthowif->birth_date->toDateString() : null,
                'address' => $muthowif->address,
                'work_location' => $muthowif->work_location,
                'work_location_label' => $muthowif->workLocationLabel(),
                'photo_url' => $muthowif->photo_url,
                'ktp_url' => $muthowif->ktp_url,
                'languages' => $muthowif->languagesForDisplay() ?: [],
                'educations' => $muthowif->educationsForDisplay() ?: [],
                'work_experiences' => $muthowif->workExperiencesForDisplay() ?: [],
                'reference_text' => $muthowif->reference_text,
                'referral_code' => $muthowif->referral_code,
                'referred_by_muthowif_profile_id' => $muthowif->referred_by_muthowif_profile_id,
                'inviter_name' => $muthowif->referredBy?->user?->name,
                'inviter_referral_code' => $muthowif->referredBy?->referral_code,
                'supporting_documents' => $muthowif->supportingDocuments->map(fn ($d) => [
                    'id' => $d->id,
                    'url' => asset('storage/'.$d->path),
                    'name' => $d->original_name,
                ]),
            ] : null,
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,'.$user->id],
        ]);

        $user->fill($request->only('name', 'email'));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return response()->json([
            'message' => 'Informasi akun berhasil diperbarui',
            'user' => $user,
        ]);
    }

    public function updatePublic(Request $request)
    {
        $user = $request->user();
        $muthowif = $user->muthowifProfile;

        if (! $muthowif) {
            return response()->json(['message' => 'Profil Muthowif tidak ditemukan'], 404);
        }

        $request->validate([
            'phone' => 'nullable|string|max:20',
            'passport_number' => 'nullable|string|max:50',
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string',
            'work_location' => 'nullable|string|max:255',
            'languages' => 'nullable|array',
            'educations' => 'nullable|array',
            'work_experiences' => 'nullable|array',
            'reference_text' => 'nullable|string',
            'inviter_referral_code' => 'nullable|string|max:16',
        ]);

        $payload = [
            'phone' => $request->phone,
            'passport_number' => $request->passport_number,
            'birth_date' => $request->birth_date,
            'address' => $request->address,
            'work_location' => filled($request->work_location) ? trim($request->work_location) : null,
            'languages' => $request->languages,
            'educations' => $request->educations,
            'work_experiences' => $request->work_experiences,
            'reference_text' => $request->reference_text,
        ];

        if ($muthowif->referred_by_muthowif_profile_id === null) {
            $codeRaw = $request->input('inviter_referral_code');
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
                if ((string) $inviter->getKey() === (string) $muthowif->getKey()) {
                    throw ValidationException::withMessages([
                        'inviter_referral_code' => [__('profile_public.referral_self_error')],
                    ]);
                }
                $payload['referred_by_muthowif_profile_id'] = (string) $inviter->getKey();
            }
        }

        $muthowif->update($payload);

        $fresh = $muthowif->fresh();
        if ($fresh !== null && $fresh->isApproved()) {
            app(MuthowifReferralCodeService::class)->ensureAssigned($fresh);
        }

        return response()->json([
            'message' => 'Profil publik berhasil diperbarui',
            'muthowif' => $muthowif->fresh()?->load('referredBy.user'),
        ]);
    }

    public function uploadPhoto(Request $request)
    {
        $user = $request->user();
        $muthowif = $user->muthowifProfile;

        $request->validate([
            'photo' => 'required|image|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            $path = app(UploadedImageOptimizer::class)->store(
                $request->file('photo'),
                'muthowif/photos',
                'public',
                'profile',
            );
            $muthowif->update(['photo_path' => $path]);

            return response()->json([
                'message' => 'Foto profil berhasil diunggah',
                'photo_url' => asset('storage/'.$path),
            ]);
        }

        return response()->json(['message' => 'File tidak ditemukan'], 400);
    }

    public function uploadKtp(Request $request)
    {
        $user = $request->user();
        $muthowif = $user->muthowifProfile;

        $request->validate([
            'ktp' => 'required|image|max:2048',
        ]);

        if ($request->hasFile('ktp')) {
            $path = app(UploadedImageOptimizer::class)->store(
                $request->file('ktp'),
                'muthowif/ktp',
                'public',
                'profile',
            );
            $muthowif->update(['ktp_image_path' => $path]);

            return response()->json([
                'message' => 'Scan KTP berhasil diunggah',
                'ktp_url' => asset('storage/'.$path),
            ]);
        }

        return response()->json(['message' => 'File tidak ditemukan'], 400);
    }

    public function uploadSupportingDocument(Request $request)
    {
        $user = $request->user();
        $muthowif = $user->muthowifProfile;

        $request->validate([
            'document' => 'required|image|max:2048',
        ]);

        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $path = app(UploadedImageOptimizer::class)->store(
                $file,
                'muthowif/documents',
                'public',
                'document',
            );

            $doc = $muthowif->supportingDocuments()->create([
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'sort_order' => $muthowif->supportingDocuments()->count() + 1,
            ]);

            return response()->json([
                'message' => 'Dokumen berhasil diunggah',
                'document' => [
                    'id' => $doc->id,
                    'path' => $doc->path,
                    'url' => asset('storage/'.$doc->path),
                    'name' => $doc->original_name,
                ],
            ]);
        }

        return response()->json(['message' => 'File tidak ditemukan'], 400);
    }

    public function deleteSupportingDocument(Request $request, $id)
    {
        $user = $request->user();
        $muthowif = $user->muthowifProfile;

        $doc = $muthowif->supportingDocuments()->findOrFail($id);
        $doc->delete();

        return response()->json(['message' => 'Dokumen berhasil dihapus']);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'Password berhasil diperbarui',
        ]);
    }
}
