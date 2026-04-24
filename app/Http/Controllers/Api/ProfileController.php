<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $muthowif = $user->muthowifProfile;

        if ($muthowif) {
            $muthowif->load('supportingDocuments');
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
                'photo_url' => $muthowif->photo_url,
                'ktp_url' => $muthowif->ktp_url,
                'languages' => $muthowif->languagesForDisplay() ?: [],
                'educations' => $muthowif->educationsForDisplay() ?: [],
                'work_experiences' => $muthowif->workExperiencesForDisplay() ?: [],
                'reference_text' => $muthowif->reference_text,
                'supporting_documents' => $muthowif->supportingDocuments->map(fn($d) => [
                    'id' => $d->id,
                    'url' => asset('storage/' . $d->path),
                    'name' => $d->original_name
                ])
            ] : null
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,' . $user->id],
        ]);

        $user->fill($request->only('name', 'email'));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return response()->json([
            'message' => 'Informasi akun berhasil diperbarui',
            'user' => $user
        ]);
    }

    public function updatePublic(Request $request)
    {
        $user = $request->user();
        $muthowif = $user->muthowifProfile;

        if (!$muthowif) {
            return response()->json(['message' => 'Profil Muthowif tidak ditemukan'], 404);
        }

        $request->validate([
            'phone' => 'nullable|string|max:20',
            'passport_number' => 'nullable|string|max:50',
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string',
            'languages' => 'nullable|array',
            'educations' => 'nullable|array',
            'work_experiences' => 'nullable|array',
            'reference_text' => 'nullable|string',
        ]);

        $muthowif->update([
            'phone' => $request->phone,
            'passport_number' => $request->passport_number,
            'birth_date' => $request->birth_date,
            'address' => $request->address,
            'languages' => $request->languages,
            'educations' => $request->educations,
            'work_experiences' => $request->work_experiences,
            'reference_text' => $request->reference_text,
        ]);

        return response()->json([
            'message' => 'Profil publik berhasil diperbarui',
            'muthowif' => $muthowif
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
            $path = $request->file('photo')->store('muthowif/photos', 'public');
            $muthowif->update(['photo_path' => $path]);
            
            return response()->json([
                'message' => 'Foto profil berhasil diunggah',
                'photo_url' => asset('storage/' . $path)
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
            $path = $request->file('ktp')->store('muthowif/ktp', 'public');
            $muthowif->update(['ktp_image_path' => $path]);
            
            return response()->json([
                'message' => 'Scan KTP berhasil diunggah',
                'ktp_url' => asset('storage/' . $path)
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
            $path = $file->store('muthowif/documents', 'public');
            
            $doc = $muthowif->supportingDocuments()->create([
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'sort_order' => $muthowif->supportingDocuments()->count() + 1
            ]);
            
            return response()->json([
                'message' => 'Dokumen berhasil diunggah',
                'document' => [
                    'id' => $doc->id,
                    'path' => $doc->path,
                    'url' => asset('storage/' . $doc->path),
                    'name' => $doc->original_name
                ]
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
            'message' => 'Password berhasil diperbarui'
        ]);
    }
}
