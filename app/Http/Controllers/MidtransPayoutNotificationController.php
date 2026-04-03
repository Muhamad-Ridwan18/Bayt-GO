<?php

namespace App\Http\Controllers;

use App\Models\MuthowifProfile;
use App\Models\MuthowifWithdrawal;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MidtransPayoutNotificationController extends Controller
{
    public function handle(Request $request): Response
    {
        $token = (string) ($request->header('x-callback-token') ?? '');
        $expected = (string) config('services.xendit.webhook_token');

        Log::debug('Xendit payout notification endpoint hit', [
            'has_x_callback_token' => $token !== '',
        ]);

        if ($expected === '' || $token === '' || ! hash_equals($expected, $token)) {
            return response('Invalid signature', 403);
        }

        $payload = $request->all();
        if (! is_array($payload)) {
            return response('Invalid payload', 400);
        }

        $event = (string) ($payload['event'] ?? '');
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];

        $payoutId = is_string($data['id'] ?? null) ? (string) $data['id'] : '';
        $status = is_string($data['status'] ?? null) ? (string) $data['status'] : '';
        $referenceId = is_string($data['reference_id'] ?? null) ? (string) $data['reference_id'] : '';

        if ($payoutId === '' || $status === '') {
            return response('Bad request', 400);
        }

        DB::transaction(function () use ($payoutId, $status, $referenceId, $payload): void {
            $withdrawal = MuthowifWithdrawal::query()
                ->where(function ($q) use ($payoutId, $referenceId): void {
                    $q->where('midtrans_reference_no', $payoutId);
                    if ($referenceId !== '') {
                        $q->orWhere('midtrans_reference_no', $referenceId);
                    }
                })
                ->lockForUpdate()
                ->first();

            if ($withdrawal === null) {
                Log::warning('Xendit payout notification: withdrawal not found', [
                    'payout_id' => $payoutId,
                    'reference_id' => $referenceId,
                ]);
                return;
            }

            $currentStatus = $withdrawal->status;

            $targetStatus = $currentStatus;
            $statusLower = strtolower($status);
            if ($event === 'payout.succeeded' || $statusLower === 'succeeded' ) {
                $targetStatus = 'succeeded';
            } elseif ($event === 'payout.failed' || $statusLower === 'failed' || $statusLower === 'rejected') {
                $targetStatus = 'failed';
            } elseif ($statusLower === 'processing' || $statusLower === 'pending') {
                $targetStatus = 'processing';
            }

            if ($targetStatus === $currentStatus) {
                $withdrawal->midtrans_notification_payload = $payload;
                $withdrawal->midtrans_initial_status = $status;
                $withdrawal->save();
                return;
            }

            $update = [
                'midtrans_initial_status' => $status,
                'midtrans_notification_payload' => $payload,
            ];

            if ($targetStatus === 'failed' && $currentStatus !== 'failed') {
                $profile = MuthowifProfile::query()
                    ->whereKey($withdrawal->muthowif_profile_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $profile->wallet_balance = round((float) $profile->wallet_balance + (float) $withdrawal->amount, 2);
                $profile->save();
            }

            if ($targetStatus === 'processing') {
                $update['status'] = 'processing';
                $update['processing_at'] = now();
            } elseif ($targetStatus === 'succeeded') {
                $update['status'] = 'succeeded';
                $update['completed_at'] = now();
            } elseif ($targetStatus === 'failed') {
                $update['status'] = 'failed';
                $update['failed_at'] = now();
                $update['failed_reason'] = $payload['data']['failure_code'] ?? $withdrawal->failed_reason;
            }

            $withdrawal->update($update);
        });

        return response('OK', 200);
    }
}

