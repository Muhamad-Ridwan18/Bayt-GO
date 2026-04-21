<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indeks tambahan untuk filter JOIN, urutan waktu, dan agregat yang sering dipakai aplikasi + queue.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('muthowif_bookings', function (Blueprint $table) {
            $table->index(['customer_id', 'status'], 'mb_customer_status_idx');
            $table->index(['customer_id', 'created_at'], 'mb_customer_created_idx');
            $table->index(['customer_id', 'status', 'ends_on'], 'mb_customer_status_ends_idx');
            $table->index(['muthowif_profile_id', 'payment_status'], 'mb_profile_paystatus_idx');
            $table->index('payment_status', 'mb_payment_status_idx');
            $table->index(['muthowif_profile_id', 'status', 'ends_on'], 'mb_profile_status_ends_idx');
            $table->index(['muthowif_profile_id', 'ends_on', 'starts_on'], 'mb_profile_ends_starts_idx');
        });

        Schema::table('booking_payments', function (Blueprint $table) {
            $table->index('wallet_credited_at', 'bp_wallet_credited_idx');
            $table->index(['muthowif_booking_id', 'settled_at'], 'bp_booking_settled_idx');
            $table->index(['status', 'created_at'], 'bp_status_created_idx');
            $table->index('midtrans_transaction_id', 'bp_midtrans_txn_idx');
        });

        Schema::table('booking_refund_requests', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'brr_status_created_idx');
            $table->index(['customer_id', 'status'], 'brr_customer_status_idx');
        });

        Schema::table('muthowif_profiles', function (Blueprint $table) {
            $table->index(['verification_status', 'created_at'], 'mp_verify_created_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index(['role', 'created_at'], 'users_role_created_idx');
        });

        Schema::table('muthowif_withdrawals', function (Blueprint $table) {
            $table->index(['muthowif_profile_id', 'requested_at'], 'mw_profile_requested_idx');
        });

        Schema::table('booking_reschedule_requests', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'brrs_status_created_idx');
        });

        Schema::table('booking_chat_messages', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'bcm_user_created_idx');
        });

        Schema::table('muthowif_supporting_documents', function (Blueprint $table) {
            $table->index(['muthowif_profile_id', 'sort_order'], 'msd_profile_sort_idx');
        });

        Schema::table('muthowif_service_add_ons', function (Blueprint $table) {
            $table->index(['muthowif_service_id', 'sort_order'], 'mso_service_sort_idx');
        });

        if (Schema::hasTable('jobs')) {
            Schema::table('jobs', function (Blueprint $table) {
                $table->index(['queue', 'available_at'], 'jobs_queue_available_idx');
            });
        }

        if (Schema::hasTable('failed_jobs')) {
            Schema::table('failed_jobs', function (Blueprint $table) {
                $table->index('failed_at', 'failed_jobs_failed_at_idx');
            });
        }

        if (Schema::hasTable('sessions')) {
            Schema::table('sessions', function (Blueprint $table) {
                $table->index(['user_id', 'last_activity'], 'sessions_user_activity_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::table('muthowif_bookings', function (Blueprint $table) {
            $table->dropIndex('mb_customer_status_idx');
            $table->dropIndex('mb_customer_created_idx');
            $table->dropIndex('mb_customer_status_ends_idx');
            $table->dropIndex('mb_profile_paystatus_idx');
            $table->dropIndex('mb_payment_status_idx');
            $table->dropIndex('mb_profile_status_ends_idx');
            $table->dropIndex('mb_profile_ends_starts_idx');
        });

        Schema::table('booking_payments', function (Blueprint $table) {
            $table->dropIndex('bp_wallet_credited_idx');
            $table->dropIndex('bp_booking_settled_idx');
            $table->dropIndex('bp_status_created_idx');
            $table->dropIndex('bp_midtrans_txn_idx');
        });

        Schema::table('booking_refund_requests', function (Blueprint $table) {
            $table->dropIndex('brr_status_created_idx');
            $table->dropIndex('brr_customer_status_idx');
        });

        Schema::table('muthowif_profiles', function (Blueprint $table) {
            $table->dropIndex('mp_verify_created_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_created_idx');
        });

        Schema::table('muthowif_withdrawals', function (Blueprint $table) {
            $table->dropIndex('mw_profile_requested_idx');
        });

        Schema::table('booking_reschedule_requests', function (Blueprint $table) {
            $table->dropIndex('brrs_status_created_idx');
        });

        Schema::table('booking_chat_messages', function (Blueprint $table) {
            $table->dropIndex('bcm_user_created_idx');
        });

        Schema::table('muthowif_supporting_documents', function (Blueprint $table) {
            $table->dropIndex('msd_profile_sort_idx');
        });

        Schema::table('muthowif_service_add_ons', function (Blueprint $table) {
            $table->dropIndex('mso_service_sort_idx');
        });

        if (Schema::hasTable('jobs')) {
            Schema::table('jobs', function (Blueprint $table) {
                $table->dropIndex('jobs_queue_available_idx');
            });
        }

        if (Schema::hasTable('failed_jobs')) {
            Schema::table('failed_jobs', function (Blueprint $table) {
                $table->dropIndex('failed_jobs_failed_at_idx');
            });
        }

        if (Schema::hasTable('sessions')) {
            Schema::table('sessions', function (Blueprint $table) {
                $table->dropIndex('sessions_user_activity_idx');
            });
        }
    }
};
