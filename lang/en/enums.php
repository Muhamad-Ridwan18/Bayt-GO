<?php

return [
    'booking_status' => [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],
    'payment_status' => [
        'pending' => 'Awaiting payment',
        'paid' => 'Paid',
        'refund_pending' => 'Refund pending admin transfer',
        'refunded' => 'Refunded',
    ],
    'muthowif_service_type' => [
        'group' => 'Group pilgrims',
        'private' => 'Private pilgrims',
    ],
    'muthowif_verification_status' => [
        'pending' => 'Pending verification',
        'approved' => 'Verified',
        'rejected' => 'Rejected',
    ],
    'booking_change_request_status' => [
        'pending' => 'Awaiting decision',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ],
    'customer_type' => [
        'personal' => 'Personal',
        'company' => 'Company',
    ],
    'user_role' => [
        'admin' => 'Admin',
        'customer' => 'Pilgrim',
        'muthowif' => 'Muthowif',
    ],
];
