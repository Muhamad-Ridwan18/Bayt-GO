<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MuthowifVerificationStatus;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessWhatsAppBroadcastJob;
use App\Models\MuthowifProfile;
use App\Services\WhatsAppBroadcastService;
use App\Support\WhatsAppMediaUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class WhatsAppBroadcastController extends Controller
{
    public function __construct(
        private readonly WhatsAppBroadcastService $broadcast
    ) {}

    public function index(Request $request): View
    {
        WhatsAppMediaUrl::ensureBroadcastStorageReady();

        $status = $request->query('status', 'all');
        if (! in_array($status, ['all', 'approved', 'pending', 'rejected'], true)) {
            $status = 'all';
        }

        $search = trim((string) $request->query('q', ''));

        $query = MuthowifProfile::query()
            ->with('user')
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->orderByDesc('created_at');

        if ($status !== 'all') {
            $query->where('verification_status', $status);
        }

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like): void {
                $q->where('phone', 'like', $like)
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', $like)->orWhere('email', 'like', $like));
            });
        }

        $muthowifs = $query->paginate(50)->withQueryString();

        $countRows = MuthowifProfile::query()
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->select('verification_status')
            ->selectRaw('count(*) as aggregate')
            ->groupBy('verification_status')
            ->get();

        $counts = [
            'all' => 0,
            'approved' => 0,
            'pending' => 0,
            'rejected' => 0,
        ];

        foreach ($countRows as $row) {
            $key = $row->verification_status instanceof MuthowifVerificationStatus
                ? $row->verification_status->value
                : (string) $row->verification_status;
            if (isset($counts[$key])) {
                $counts[$key] = (int) $row->aggregate;
            }
            $counts['all'] += (int) $row->aggregate;
        }

        return view('admin.whatsapp-broadcast.index', [
            'muthowifs' => $muthowifs,
            'status' => $status,
            'search' => $search,
            'counts' => $counts,
            'whatsappConfigured' => $this->broadcast->whatsappConfigured(),
            'mediaUrlPublic' => WhatsAppMediaUrl::isPubliclyReachable(),
            'mediaBaseUrl' => WhatsAppMediaUrl::baseUrl(),
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        if (! $this->broadcast->whatsappConfigured()) {
            return back()
                ->withInput()
                ->with('error', __('admin.whatsapp_broadcast.token_missing'));
        }

        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:4000', 'required_without:attachment'],
            'attachment' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp,pdf', 'max:10240'],
            'muthowif_profile_ids' => ['nullable', 'array', 'max:500'],
            'muthowif_profile_ids.*' => ['uuid', 'exists:muthowif_profiles,id'],
            'free_numbers' => ['nullable', 'string', 'max:10000'],
        ], [
            'message.required_without' => __('admin.whatsapp_broadcast.message_or_attachment_required'),
            'message.max' => __('admin.whatsapp_broadcast.message_max'),
            'attachment.mimes' => __('admin.whatsapp_broadcast.attachment_mimes'),
            'attachment.max' => __('admin.whatsapp_broadcast.attachment_max'),
        ]);

        $profileIds = $validated['muthowif_profile_ids'] ?? [];
        $freeNumbers = trim((string) ($validated['free_numbers'] ?? ''));

        if ($profileIds === [] && $freeNumbers === '') {
            return back()
                ->withInput()
                ->withErrors(['recipients' => __('admin.whatsapp_broadcast.recipients_required')]);
        }

        $preview = $this->broadcast->resolveRecipients($profileIds, $freeNumbers);

        if ($preview['recipients'] === []) {
            $message = __('admin.whatsapp_broadcast.no_valid_recipients');
            if ($preview['invalid_numbers'] !== []) {
                $message .= ' '.implode(', ', array_slice($preview['invalid_numbers'], 0, 5));
            }

            return back()
                ->withInput()
                ->withErrors(['recipients' => $message]);
        }

        if (count($preview['recipients']) > 500) {
            return back()
                ->withInput()
                ->withErrors(['recipients' => __('admin.whatsapp_broadcast.too_many_recipients')]);
        }

        $attachmentLocalPath = null;
        $attachmentPublicUrl = null;
        $attachmentFilename = null;
        /** @var UploadedFile|null $attachment */
        $attachment = $request->file('attachment');
        if ($attachment !== null) {
            $storedPath = $attachment->store(WhatsAppMediaUrl::ensureBroadcastStorageReady(), 'public');
            $attachmentLocalPath = Storage::disk('public')->path($storedPath);
            $attachmentPublicUrl = WhatsAppMediaUrl::forPublicDiskPath($storedPath);
            $attachmentFilename = $attachment->getClientOriginalName();
        }

        ProcessWhatsAppBroadcastJob::dispatch(
            trim((string) ($validated['message'] ?? '')),
            $profileIds,
            $freeNumbers,
            $attachmentLocalPath,
            $attachmentFilename,
            $attachmentPublicUrl,
        );

        return back()->with(
            'status',
            __('admin.whatsapp_broadcast.queued', ['count' => count($preview['recipients'])]),
        );
    }
}
