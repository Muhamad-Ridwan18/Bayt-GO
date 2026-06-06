<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SupportTicketStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use App\Services\SupportTicketAttachmentStore;
use App\Support\SupportTicketBroadcast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupportTicketsController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', SupportTicket::class);

        $q = trim((string) $request->query('q', ''));
        $statusFilter = $request->query('status');

        $tickets = SupportTicket::query()
            ->with(['reporter:id,name,email,role', 'assignedAdmin:id,name'])
            ->withCount('messages')
            ->when($q !== '', function ($query) use ($q): void {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';
                $query->where(function ($nested) use ($like): void {
                    $nested->where('code', 'like', $like)
                        ->orWhere('subject', 'like', $like);
                });
            })
            ->when(is_string($statusFilter) && $statusFilter !== '' && SupportTicketStatus::tryFrom($statusFilter), function ($query) use ($statusFilter): void {
                $query->where('status', SupportTicketStatus::from($statusFilter));
            })
            ->orderByDesc('last_activity_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.support-tickets.index', [
            'tickets' => $tickets,
            'q' => $q,
            'statusFilter' => is_string($statusFilter) ? $statusFilter : '',
            'statuses' => SupportTicketStatus::cases(),
        ]);
    }

    public function show(Request $request, SupportTicket $ticket): View
    {
        $this->authorize('view', $ticket);

        $ticket->load([
            'messages.author:id,name,email,role',
            'reporter:id,name,email,role,phone',
            'assignedAdmin:id,name,email',
        ]);

        $admins = User::query()
            ->where('role', UserRole::Admin)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.support-tickets.show', [
            'ticket' => $ticket,
            'canReply' => $request->user()->can('reply', $ticket),
            'statuses' => SupportTicketStatus::cases(),
            'admins' => $admins,
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
                'is_staff' => true,
            ]);

            $stored = SupportTicketAttachmentStore::storeFromRequest($request, $ticket, $message);
            if ($stored !== []) {
                $message->update(['attachments' => $stored]);
            }

            if ($ticket->status === SupportTicketStatus::Open) {
                $ticket->status = SupportTicketStatus::InProgress;
            }

            $ticket->last_activity_at = now();
            $ticket->save();
        });

        SupportTicketBroadcast::afterResponse($ticket->fresh(), 'reply');

        return redirect()
            ->route('admin.support-tickets.show', $ticket)
            ->with('status', __('support.flash.admin_reply_sent'));
    }

    public function update(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $this->authorize('update', $ticket);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(SupportTicketStatus::class)],
            'assigned_admin_id' => ['nullable', 'uuid', 'exists:users,id'],
        ]);

        $assignee = null;
        $rawAssign = $validated['assigned_admin_id'] ?? null;
        if ($rawAssign !== null && $rawAssign !== '') {
            $assignee = User::query()->findOrFail($rawAssign);
            if (! $assignee->isAdmin()) {
                return redirect()
                    ->route('admin.support-tickets.show', $ticket)
                    ->with('error', __('support.flash.assignee_not_admin'));
            }
        }

        $status = SupportTicketStatus::from($validated['status']);

        $ticket->status = $status;
        $ticket->assigned_admin_id = $assignee?->getKey();
        $ticket->closed_at = $status === SupportTicketStatus::Closed ? now() : null;
        $ticket->last_activity_at = now();
        $ticket->save();

        SupportTicketBroadcast::afterResponse($ticket->fresh(), 'status_updated');

        return redirect()
            ->route('admin.support-tickets.show', $ticket)
            ->with('status', __('support.flash.ticket_updated'));
    }

    public function assignSelf(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $this->authorize('assign', $ticket);

        $ticket->assigned_admin_id = $request->user()->getKey();
        if ($ticket->status === SupportTicketStatus::Open) {
            $ticket->status = SupportTicketStatus::InProgress;
        }
        $ticket->last_activity_at = now();
        $ticket->save();

        SupportTicketBroadcast::afterResponse($ticket->fresh(), 'assigned');

        return redirect()
            ->route('admin.support-tickets.show', $ticket)
            ->with('status', __('support.flash.assigned_self'));
    }

    public function indexLiveFragment(Request $request): View
    {
        $this->authorize('viewAny', SupportTicket::class);

        $q = trim((string) $request->query('q', ''));
        $statusFilter = $request->query('status');

        $tickets = SupportTicket::query()
            ->with(['reporter:id,name,email,role', 'assignedAdmin:id,name'])
            ->withCount('messages')
            ->when($q !== '', function ($query) use ($q): void {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';
                $query->where(function ($nested) use ($like): void {
                    $nested->where('code', 'like', $like)
                        ->orWhere('subject', 'like', $like);
                });
            })
            ->when(is_string($statusFilter) && $statusFilter !== '' && SupportTicketStatus::tryFrom($statusFilter), function ($query) use ($statusFilter): void {
                $query->where('status', SupportTicketStatus::from($statusFilter));
            })
            ->orderByDesc('last_activity_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.support-tickets.partials.index-live', [
            'tickets' => $tickets,
        ]);
    }

    public function showLiveFragment(Request $request, SupportTicket $ticket): View
    {
        $this->authorize('view', $ticket);

        $ticket->load([
            'messages.author:id,name,email,role',
        ]);

        return view('admin.support-tickets.partials.thread-live', [
            'ticket' => $ticket,
        ]);
    }
}
