<?php

namespace App\Http\Controllers;

use App\Enums\SupportTicketCategory;
use App\Enums\SupportTicketPriority;
use App\Enums\SupportTicketStatus;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Services\SupportTicketAttachmentStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $tickets = SupportTicket::query()
            ->where('user_id', $user->getKey())
            ->withCount('messages')
            ->orderByDesc(DB::raw('COALESCE(last_activity_at, created_at)'))
            ->paginate(15)
            ->withQueryString();

        return view('support.index', [
            'tickets' => $tickets,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', SupportTicket::class);

        return view('support.create', [
            'categories' => SupportTicketCategory::cases(),
            'priorities' => SupportTicketPriority::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
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

        return redirect()
            ->route('support.show', $ticket)
            ->with('status', __('support.flash.created'));
    }

    public function show(Request $request, SupportTicket $ticket): View
    {
        $this->authorize('view', $ticket);

        $ticket->load([
            'messages.author:id,name,email,role',
            'assignedAdmin:id,name',
        ]);

        return view('support.show', [
            'ticket' => $ticket,
            'canReply' => $request->user()->can('reply', $ticket),
        ]);
    }

    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
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

            if ($ticket->status === SupportTicketStatus::AwaitingCustomer) {
                $ticket->status = SupportTicketStatus::InProgress;
            }

            $ticket->last_activity_at = now();
            $ticket->save();
        });

        return redirect()
            ->route('support.show', $ticket)
            ->with('status', __('support.flash.reply_sent'));
    }
}
