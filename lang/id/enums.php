<?php

return [
    'booking_status' => [
        'pending' => 'Menunggu',
        'confirmed' => 'Terkonfirmasi',
        'in_progress' => 'Berjalan',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan',
    ],
    'payment_status' => [
        'pending' => 'Menunggu pembayaran',
        'paid' => 'Lunas',
        'refund_pending' => 'Refund menunggu transfer admin',
        'refunded' => 'Dikembalikan (refund)',
    ],
    'muthowif_service_type' => [
        'group' => 'Jemaah Group',
        'private' => 'Jemaah Private',
        'support' => 'Layanan Pendukung',
    ],
    'muthowif_work_location' => [
        'makkah' => 'Mekkah',
        'madinah' => 'Madinah',
        'makkah_madinah' => 'Mekkah & Madinah',
    ],
    'support_package_category' => [
        'tawaf' => 'Tawaf',
        'umrah' => 'Umrah',
        'ziarah' => 'Ziarah',
        'mobility' => 'Mobilitas / kursi roda',
        'other' => 'Fotografer & Videografer',
    ],
    'muthowif_verification_status' => [
        'pending' => 'Menunggu verifikasi',
        'approved' => 'Terverifikasi',
        'rejected' => 'Ditolak',
    ],
    'booking_change_request_status' => [
        'pending' => 'Menunggu keputusan',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
    ],
    'customer_type' => [
        'personal' => 'Personal',
        'company' => 'Perusahaan',
    ],
    'user_role' => [
        'admin' => 'Admin',
        'customer' => 'Jamaah',
        'muthowif' => 'Muthowif',
    ],
    'support_ticket_category' => [
        'bug' => 'Bug / masalah teknis',
        'booking' => 'Pesanan',
        'payment' => 'Pembayaran',
        'account' => 'Akun & profil',
        'suggestion' => 'Saran',
        'other' => 'Lainnya',
    ],
    'support_ticket_priority' => [
        'low' => 'Rendah',
        'normal' => 'Normal',
        'high' => 'Tinggi',
    ],
    'support_ticket_status' => [
        'open' => 'Terbuka',
        'in_progress' => 'Diproses',
        'awaiting_customer' => 'Menunggu balasan Anda',
        'resolved' => 'Selesai',
        'closed' => 'Ditutup',
    ],
    'muthowif_booking_muthowif_rejection_kind' => [
        'jadwal_full' => 'Jadwal muthowif penuh',
        'illness' => 'Sakit / berhalangan',
        'force_majeure' => 'Force majeure',
        'other' => 'Alasan lain',
    ],
];
