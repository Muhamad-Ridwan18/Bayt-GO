<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            // Composite index untuk user view + sorting aktivitas
            $table->index(['user_id', 'last_activity_at'], 'st_user_last_activity_idx');

            // Composite index untuk admin view + status filter + sorting aktivitas
            $table->index(['status', 'last_activity_at'], 'st_status_last_activity_idx');

            // Single index untuk admin sorting global
            $table->index('last_activity_at', 'st_last_activity_idx');
        });

        Schema::table('booking_payments', function (Blueprint $table) {
            // Composite index untuk filter status dan tren bulanan settled_at
            $table->index(['status', 'settled_at'], 'bp_status_settled_idx');
        });

        Schema::table('booking_refund_requests', function (Blueprint $table) {
            // Composite index untuk filter status dan tren bulanan decided_at
            $table->index(['status', 'decided_at'], 'brr_status_decided_idx');
        });

        Schema::table('muthowif_profiles', function (Blueprint $table) {
            // Composite index untuk filter status persetujuan dan pengurutan waktu verifikasi (Muthowif directory)
            $table->index(['verification_status', 'verified_at'], 'mp_status_verified_idx');
        });
    }

    public function down(): void
    {
        Schema::table('muthowif_profiles', function (Blueprint $table) {
            $table->dropIndex('mp_status_verified_idx');
        });

        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropIndex('st_user_last_activity_idx');
            $table->dropIndex('st_status_last_activity_idx');
            $table->dropIndex('st_last_activity_idx');
        });

        Schema::table('booking_payments', function (Blueprint $table) {
            $table->dropIndex('bp_status_settled_idx');
        });

        Schema::table('booking_refund_requests', function (Blueprint $table) {
            $table->dropIndex('brr_status_decided_idx');
        });
    }
};
