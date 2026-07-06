<?php

namespace App\Http\Controllers\Api;

use App\Enums\SupportTicketCategory;
use App\Enums\SupportTicketPriority;
use App\Enums\SupportTicketStatus;
use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Services\SupportTicketAttachmentStore;
use App\Support\SupportTicketBroadcast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SupportTicketApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tickets = SupportTicket::query()
            ->where('user_id', $request->user()->getKey())
            ->withCount('messages')
            ->orderByDesc('last_activity_at')
            ->paginate(20);

        return response()->json([
            'data' => $tickets->getCollection()->map(fn (SupportTicket $t) => $this->formatTicket($t))->values(),
            'meta' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'total' => $tickets->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', SupportTicket::class);

        $validated = $request->validate(array_merge([
            'subject' => ['required', 'string', 'max:160'],
            'category' => ['required', Rule::enum(SupportTicketCategory::class)],
            'priority' => ['required', Rule::enum(SupportTicketPriority::class)],
            'body' => ['required', 'string', 'max:12000'],
        ], SupportTicketAttachmentStore::validationRules()));

        $ticket = DB::transaction(function () use ($request, $validated): SupportTicket {
            $ticket = SupportTicket::create([
                'user_id' => $request->user()->getKey(),
                'subject' => $validated['subject'],
                'category' => SupportTicketCategory::from($validated['category']),
                'priority' => SupportTicketPriority::from($validated['priority']),
                'status' => SupportTicketStatus::Open,
                'last_activity_at' => now(),
            ]);

            $message = SupportTicketMessage::create([
                'support_ticket_id' => $ticket->getKey(),
                'user_id' => $request->user()->getKey(),
                'body' => $validated['body'],
                'is_staff' => false,
            ]);

            $stored = SupportTicketAttachmentStore::storeFromRequest($request, $ticket, $message);
            if ($stored !== []) {
                $message->update(['attachments' => $stored]);
            }

            return $ticket;
        });

        SupportTicketBroadcast::afterResponse($ticket, 'created');

        return response()->json([
            'message' => __('support.flash.created'),
            'ticket' => $this->formatTicket($ticket->fresh()->loadCount('messages')),
        ], 201);
    }

    public function show(Request $request, SupportTicket $ticket): JsonResponse
    {
        $this->authorize('view', $ticket);

        $ticket->load([
            'messages.author:id,name,email,role',
            'assignedAdmin:id,name',
        ]);

        return response()->json([
            'ticket' => $this->formatTicket($ticket, true),
            'can_reply' => $request->user()->can('reply', $ticket),
        ]);
    }

    public function reply(Request $request, SupportTicket $ticket): JsonResponse
    {
        $this->authorize('reply', $ticket);

        $validated = $request->validate(array_merge([
            'body' => ['required', 'string', 'max:12000'],
        ], SupportTicketAttachmentStore::validationRules()));

        DB::transaction(function () use ($request, $validated, $ticket): void {
            $message = SupportTicketMessage::create([
                'support_ticket_id' => $ticket->getKey(),
                'user_id' => $request->user()->getKey(),
                'body' => $validated['body'],
                'is_staff' => false,
            ]);

            $stored = SupportTicketAttachmentStore::storeFromRequest($request, $ticket, $message);
            if ($stored !== []) {
                $message->update(['attachments' => $stored]);
            }

            $ticket->update(['last_activity_at' => now()]);
        });

        SupportTicketBroadcast::afterResponse($ticket->fresh(), 'replied');

        return response()->json(['message' => __('support.flash.replied')]);
    }

    public function meta(): JsonResponse
    {
        return response()->json([
            'categories' => collect(SupportTicketCategory::cases())->map(fn ($c) => [
                'value' => $c->value,
                'label' => $c->label(),
            ])->values(),
            'priorities' => collect(SupportTicketPriority::cases())->map(fn ($p) => [
                'value' => $p->value,
                'label' => $p->label(),
            ])->values(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatTicket(SupportTicket $ticket, bool $withMessages = false): array
    {
        $data = [
            'id' => (string) $ticket->getKey(),
            'subject' => $ticket->subject,
            'category' => $ticket->category->value,
            'category_label' => $ticket->category->label(),
            'priority' => $ticket->priority->value,
            'priority_label' => $ticket->priority->label(),
            'status' => $ticket->status->value,
            'status_label' => $ticket->status->label(),
            'messages_count' => (int) ($ticket->messages_count ?? $ticket->messages()->count()),
            'last_activity_at' => $ticket->last_activity_at?->toIso8601String(),
            'created_at' => $ticket->created_at?->toIso8601String(),
        ];

        if ($withMessages) {
            $data['messages'] = $ticket->messages->map(fn (SupportTicketMessage $m) => [
                'id' => (string) $m->getKey(),
                'body' => $m->body,
                'is_staff' => (bool) $m->is_staff,
                'author_name' => $m->author?->name ?? '—',
                'attachments' => $m->attachmentUrls(),
                'created_at' => $m->created_at?->toIso8601String(),
            ])->values();
        }

        return $data;
    }
}
