<?php

return [
    'booking_status' => [
        'pending' => 'Menunggu',
        'confirmed' => 'Terkonfirmasi',
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
];
