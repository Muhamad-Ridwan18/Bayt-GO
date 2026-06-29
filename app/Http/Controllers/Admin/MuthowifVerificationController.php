<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MuthowifAccountStatus;
use App\Enums\MuthowifVerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\MuthowifProfile;
use App\Models\MuthowifSupportingDocument;
use App\Jobs\SendWhatsAppTextJob;
use App\Services\MuthowifReferralCodeService;
use App\Support\IntlPhone;
use App\Support\WhatsAppNotifySettings;
use App\Support\MuthowifVerificationBroadcast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MuthowifVerificationController extends Controller
{
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

        $countRows = MuthowifProfile::query()
            ->select('verification_status')
            ->selectRaw('count(*) as aggregate')
            ->groupBy('verification_status')
            ->get();

        $counts = [
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
        ];
        foreach ($countRows as $row) {
            $key = $row->verification_status instanceof MuthowifVerificationStatus
                ? $row->verification_status->value
                : (string) $row->verification_status;
            if (array_key_exists($key, $counts)) {
                $counts[$key] = (int) $row->aggregate;
            }
        }

        return view('admin.muthowif.index', [
            'profiles' => $query->paginate(8)->withQueryString(),
            'currentStatus' => $status,
            'counts' => $counts,
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

        app(MuthowifReferralCodeService::class)->ensureAssigned($profile->fresh());
        $fonnteDial = IntlPhone::fonnteDial($profile->phone);
        if ($fonnteDial !== null && WhatsAppNotifySettings::hasToken()) {
            $appName = config('app.name', 'BaytGo');
            $name = $profile->user->name;
            $message = "Halo *{$name}*,\n\nPendaftaran muthowif Anda di *{$appName}* telah *disetujui*.\n\nAnda sekarang dapat masuk ke akun menggunakan email terdaftar.\nDan menentukan rate card harian mu😉\nhttps://baytgo.id/login\n\nTerima kasih.";

            SendWhatsAppTextJob::dispatchAfterResponse(
                $fonnteDial['target'],
                $message,
                $fonnteDial['country_calling_code'],
            );
            $waFlash = ' Notifikasi WhatsApp sedang dikirim.';
        } elseif ($fonnteDial === null) {
            $waFlash = ' Nomor WhatsApp tidak valid — notifikasi WA dilewati.';
        } elseif (! filled($token)) {
            $waFlash = ' Token WhatsApp belum diatur — notifikasi WA dilewati.';
        }

        MuthowifVerificationBroadcast::afterResponse($profile->fresh());

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

        MuthowifVerificationBroadcast::afterResponse($profile->fresh());

        return redirect()
            ->route('admin.muthowif.show', $profile)
            ->with('status', 'Pendaftaran ditolak.');
    }

    public function updateAccountStatus(Request $request, MuthowifProfile $profile): RedirectResponse
    {
        if (! $profile->isApproved()) {
            return redirect()
                ->route('admin.muthowif.show', $profile)
                ->with('error', __('admin.muthowif.account_status_only_approved'));
        }

        $validated = $request->validate([
            'account_status' => ['required', Rule::enum(MuthowifAccountStatus::class)],
        ]);

        $status = $validated['account_status'] instanceof MuthowifAccountStatus
            ? $validated['account_status']
            : MuthowifAccountStatus::from((string) $validated['account_status']);

        $profile->update(['account_status' => $status]);

        return redirect()
            ->route('admin.muthowif.show', $profile)
            ->with('status', __('admin.muthowif.account_status_updated'));
    }

    public function indexLiveFragment(Request $request): View
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

        return view('admin.muthowif.partials.index-live', [
            'profiles' => $query->paginate(8)->withQueryString(),
        ]);
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
