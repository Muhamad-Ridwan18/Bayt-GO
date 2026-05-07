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
];
