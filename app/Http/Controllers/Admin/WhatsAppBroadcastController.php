<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MuthowifVerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\MuthowifProfile;
use App\Services\WhatsAppBroadcastService;
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

        $muthowifs = $query->get();

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

        $attachmentPublicUrl = null;
        $attachmentFilename = null;
        /** @var UploadedFile|null $attachment */
        $attachment = $request->file('attachment');
        if ($attachment !== null) {
            $path = $attachment->store('whatsapp-broadcast/'.now()->format('Y-m'), 'public');
            $attachmentPublicUrl = url(Storage::disk('public')->url($path));

            $mime = (string) $attachment->getMimeType();
            if (! str_starts_with($mime, 'image/')) {
                $attachmentFilename = $attachment->getClientOriginalName();
            }
        }

        $result = $this->broadcast->send(
            trim((string) ($validated['message'] ?? '')),
            $profileIds,
            $freeNumbers,
            $attachmentPublicUrl,
            $attachmentFilename,
        );

        $statusParts = [
            __('admin.whatsapp_broadcast.result_sent', ['count' => $result['sent']]),
        ];

        if ($result['failed'] > 0) {
            $statusParts[] = __('admin.whatsapp_broadcast.result_failed', ['count' => $result['failed']]);
        }

        if ($result['invalid_numbers'] !== []) {
            $invalidSample = implode(', ', array_slice($result['invalid_numbers'], 0, 5));
            $statusParts[] = __('admin.whatsapp_broadcast.result_invalid', [
                'count' => count($result['invalid_numbers']),
                'sample' => $invalidSample,
            ]);
        }

        $redirect = back()->with('status', implode(' ', $statusParts));

        if ($result['failures'] !== []) {
            $redirect->with('broadcast_failures', array_slice($result['failures'], 0, 20));
        }

        return $redirect;
    }
}
