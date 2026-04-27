<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('booking_payments', 'snap_token')) {
            Schema::table('booking_payments', function (Blueprint $table): void {
                $table->renameColumn('snap_token', 'checkout_token');
                $table->renameColumn('midtrans_transaction_id', 'gateway_transaction_id');
                $table->renameColumn('midtrans_notification_payload', 'gateway_notification_payload');
            });
        }

        Schema::table('booking_payments', function (Blueprint $table): void {
            if (Schema::hasIndex('booking_payments', 'bp_midtrans_txn_idx')) {
                $table->dropIndex('bp_midtrans_txn_idx');
            }
            if (! Schema::hasIndex('booking_payments', 'bp_gateway_txn_idx')) {
                $table->index('gateway_transaction_id', 'bp_gateway_txn_idx');
            }
        });

        if (Schema::hasColumn('booking_refund_requests', 'midtrans_refund_key')) {
            Schema::table('booking_refund_requests', function (Blueprint $table): void {
                $table->renameColumn('midtrans_refund_key', 'gateway_refund_key');
                $table->renameColumn('midtrans_refunded_at', 'gateway_refunded_at');
                $table->renameColumn('midtrans_refund_response', 'gateway_refund_response');
            });
        }

        if (Schema::hasColumn('muthowif_withdrawals', 'midtrans_reference_no')) {
            Schema::table('muthowif_withdrawals', function (Blueprint $table): void {
                $table->renameColumn('midtrans_reference_no', 'gateway_reference_no');
                $table->renameColumn('midtrans_initial_status', 'gateway_initial_status');
                $table->renameColumn('midtrans_notification_payload', 'gateway_notification_payload');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('booking_payments', 'bp_gateway_txn_idx')) {
            Schema::table('booking_payments', function (Blueprint $table): void {
                $table->dropIndex('bp_gateway_txn_idx');
            });
        }

        if (Schema::hasColumn('booking_payments', 'checkout_token')) {
            Schema::table('booking_payments', function (Blueprint $table): void {
                $table->renameColumn('checkout_token', 'snap_token');
                $table->renameColumn('gateway_transaction_id', 'midtrans_transaction_id');
                $table->renameColumn('gateway_notification_payload', 'midtrans_notification_payload');
            });
        }

        if (! Schema::hasIndex('booking_payments', 'bp_midtrans_txn_idx')
            && Schema::hasColumn('booking_payments', 'midtrans_transaction_id')) {
            Schema::table('booking_payments', function (Blueprint $table): void {
                $table->index('midtrans_transaction_id', 'bp_midtrans_txn_idx');
            });
        }

        if (Schema::hasColumn('booking_refund_requests', 'gateway_refund_key')) {
            Schema::table('booking_refund_requests', function (Blueprint $table): void {
                $table->renameColumn('gateway_refund_key', 'midtrans_refund_key');
                $table->renameColumn('gateway_refunded_at', 'midtrans_refunded_at');
                $table->renameColumn('gateway_refund_response', 'midtrans_refund_response');
            });
        }

        if (Schema::hasColumn('muthowif_withdrawals', 'gateway_reference_no')) {
            Schema::table('muthowif_withdrawals', function (Blueprint $table): void {
                $table->renameColumn('gateway_reference_no', 'midtrans_reference_no');
                $table->renameColumn('gateway_initial_status', 'midtrans_initial_status');
                $table->renameColumn('gateway_notification_payload', 'midtrans_notification_payload');
            });
        }
    }
};
