<?php

return [
    'booking_status' => [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'in_progress' => 'In progress',
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
        'support' => 'Support service',
    ],
    'muthowif_work_location' => [
        'makkah' => 'Makkah',
        'madinah' => 'Madinah',
        'makkah_madinah' => 'Makkah & Madinah',
    ],
    'support_package_category' => [
        'tawaf' => 'Tawaf',
        'umrah' => 'Umrah',
        'ziarah' => 'Ziarah',
        'mobility' => 'Mobility / wheelchair',
        'other' => 'Other',
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
    'support_ticket_category' => [
        'bug' => 'Bug / Technical issue',
        'booking' => 'Booking',
        'payment' => 'Payment',
        'account' => 'Account & profile',
        'suggestion' => 'Suggestion',
        'other' => 'Other',
    ],
    'support_ticket_priority' => [
        'low' => 'Low',
        'normal' => 'Normal',
        'high' => 'High',
    ],
    'support_ticket_status' => [
        'open' => 'Open',
        'in_progress' => 'In progress',
        'awaiting_customer' => 'Awaiting your reply',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
    ],
    'muthowif_booking_muthowif_rejection_kind' => [
        'jadwal_full' => 'Muthowif schedule full',
        'illness' => 'Illness / unable to attend',
        'force_majeure' => 'Force majeure',
        'other' => 'Other reason',
    ],
];
