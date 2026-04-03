<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MuthowifVerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\MuthowifProfile;
use App\Models\MuthowifSupportingDocument;
use App\Services\FonnteService;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class MuthowifVerificationController extends Controller
{
    public function __construct(
        private readonly FonnteService $fonnte
    ) {}

    public function index(Request $request): View
    {
        $status = $request->query('status', 'pending');
        if (! in_array($status, ['pending', 'approved', 'rejected', 'all'], true)) {
            $status = 'pending';
        }

        $query = MuthowifProfile::query()
            ->with('user')
            ->orderByDesc('created_at');

        if ($status !== 'all') {
            $query->where('verification_status', $status);
        }

        return view('admin.muthowif.index', [
            'profiles' => $query->paginate(15)->withQueryString(),
            'currentStatus' => $status,
            'counts' => [
                'pending' => MuthowifProfile::query()->where('verification_status', MuthowifVerificationStatus::Pending)->count(),
                'approved' => MuthowifProfile::query()->where('verification_status', MuthowifVerificationStatus::Approved)->count(),
                'rejected' => MuthowifProfile::query()->where('verification_status', MuthowifVerificationStatus::Rejected)->count(),
            ],
        ]);
    }

    public function show(MuthowifProfile $profile): View
    {
        $profile->load(['user', 'supportingDocuments']);

        return view('admin.muthowif.show', [
            'profile' => $profile,
        ]);
    }

    public function approve(MuthowifProfile $profile): RedirectResponse
    {
        if (! $profile->isPending()) {
            return redirect()
                ->route('admin.muthowif.show', $profile)
                ->with('error', 'Pendaftar ini tidak lagi dalam status menunggu.');
        }

        $profile->loadMissing('user');

        $profile->update([
            'verification_status' => MuthowifVerificationStatus::Approved,
            'verified_at' => now(),
            'rejection_reason' => null,
        ]);

        $waFlash = null;
        $target = PhoneNumber::forFonnte($profile->phone);
        $token = config('services.fonnte.token');
        if ($target !== null && filled($token)) {
            $appName = config('app.name', 'BaytGo');
            $name = $profile->user->name;
            $message = "Halo *{$name}*,\n\nPendaftaran muthowif Anda di *{$appName}* telah *disetujui*.\n\nAnda sekarang dapat masuk ke akun menggunakan email terdaftar.\n\nTerima kasih.";

            try {
                $this->fonnte->sendText($target, $message);
                $waFlash = ' Notifikasi WhatsApp terkirim.';
            } catch (Throwable $e) {
                Log::warning('WhatsApp verifikasi muthowif gagal', [
                    'profile_id' => $profile->id,
                    'exception' => $e->getMessage(),
                ]);
                $waFlash = ' Notifikasi WhatsApp gagal dikirim; cek log atau Fonnte.';
            }
        } elseif ($target === null) {
            $waFlash = ' Nomor WhatsApp tidak valid — notifikasi WA dilewati.';
        } elseif (! filled($token)) {
            $waFlash = ' FONNTE_TOKEN kosong — notifikasi WA dilewati.';
        }

        return redirect()
            ->route('admin.muthowif.show', $profile)
            ->with('status', 'Pendaftaran muthowif disetujui.'.$waFlash);
    }

    public function reject(Request $request, MuthowifProfile $profile): RedirectResponse
    {
        if (! $profile->isPending()) {
            return redirect()
                ->route('admin.muthowif.show', $profile)
                ->with('error', 'Pendaftar ini tidak lagi dalam status menunggu.');
        }

        $validated = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:5000'],
        ]);

        $profile->update([
            'verification_status' => MuthowifVerificationStatus::Rejected,
            'verified_at' => null,
            'rejection_reason' => $validated['rejection_reason'] ?? null,
        ]);

        return redirect()
            ->route('admin.muthowif.show', $profile)
            ->with('status', 'Pendaftaran ditolak.');
    }

    public function photo(MuthowifProfile $profile)
    {
        $disk = Storage::disk('local');
        if (! $disk->exists($profile->photo_path)) {
            abort(404);
        }

        return $disk->response($profile->photo_path);
    }

    public function ktp(MuthowifProfile $profile)
    {
        $disk = Storage::disk('local');
        if (! $disk->exists($profile->ktp_image_path)) {
            abort(404);
        }

        return $disk->response($profile->ktp_image_path);
    }

    public function supportingDocument(MuthowifProfile $profile, MuthowifSupportingDocument $document)
    {
        $document->loadMissing('muthowifProfile');
        if ($document->muthowif_profile_id !== $profile->id) {
            abort(404);
        }

        $disk = Storage::disk('local');
        if (! $disk->exists($document->path)) {
            abort(404);
        }

        return $disk->response($document->path, $document->original_name ?? basename($document->path));
    }
}
