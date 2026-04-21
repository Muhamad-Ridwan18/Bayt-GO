<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingChangeRequestStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Jobs\NotifyCustomerOfRefundTransferProof;
use App\Models\BookingRefundRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;

class BookingRefundController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', BookingRefundRequest::class);

        $pending = BookingRefundRequest::query()
            ->where('status', BookingChangeRequestStatus::Pending)
            ->with([
                'muthowifBooking.muthowifProfile.user',
                'muthowifBooking.customer',
                'customer',
            ])
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('admin.refunds.index', [
            'pendingRefunds' => $pending,
        ]);
    }

    public function complete(Request $request, BookingRefundRequest $refund): RedirectResponse
    {
        $this->authorize('complete', $refund);

        $validated = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:2000'],
            'transfer_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
        ]);

        if (! $refund->isPending()) {
            return redirect()
                ->route('admin.refunds.index')
                ->with('error', 'Permintaan refund ini sudah diproses.');
        }

        $booking = $refund->muthowifBooking;
        if ($booking === null) {
            return redirect()
                ->route('admin.refunds.index')
                ->with('error', 'Booking tidak ditemukan.');
        }

        if ($booking->payment_status !== PaymentStatus::RefundPending) {
            return redirect()
                ->route('admin.refunds.index')
                ->with('error', 'Status pembayaran booking tidak menunggu refund.');
        }

        $proofPath = $request->file('transfer_proof')->store('refunds/proofs', 'public');

        try {
            DB::transaction(function () use ($refund, $booking, $request, $validated, $proofPath): void {
                $refund->refresh()->lockForUpdate();
                $booking->refresh()->lockForUpdate();

                if (! $refund->isPending()) {
                    throw new RuntimeException('Permintaan refund sudah diproses.');
                }

                if ($booking->payment_status !== PaymentStatus::RefundPending) {
                    throw new RuntimeException('Status pembayaran tidak valid.');
                }

                $refund->update([
                    'status' => BookingChangeRequestStatus::Approved,
                    'decided_at' => now(),
                    'decided_by' => $request->user()->id,
                    'admin_note' => filled($validated['admin_note'] ?? null) ? trim((string) $validated['admin_note']) : null,
                    'transfer_proof_path' => $proofPath,
                ]);

                $booking->update([
                    'payment_status' => PaymentStatus::Refunded,
                ]);
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($proofPath);

            return redirect()
                ->route('admin.refunds.index')
                ->withInput()
                ->with('error', $e->getMessage());
        }

        NotifyCustomerOfRefundTransferProof::dispatchAfterResponse((string) $refund->getKey());

        return redirect()
            ->route('admin.refunds.index')
            ->with('status', 'Transfer refund dicatat selesai. Bukti akan dikirim ke WhatsApp jamaah jika nomor valid.');
    }
}
