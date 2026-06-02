<?php

return [
    'policy_version' => env('INCIDENT_POLICY_VERSION', '2026-06'),

    'chat_response_sla_hours' => 4,
    'h1_confirmation_deadline' => '18:00',
    'no_show_grace_minutes' => 30,
    'emergency_rate_limit_hours' => 6,

    'replacement_sla_hours' => [
        'critical' => 4,
        'high' => 12,
        'medium' => 24,
    ],

    'strikes_before_suspend' => 3,

    'default_primary_days_on_replacement' => null,

    /** Buka rekrutmen & broadcast otomatis saat insiden pengganti (tanpa klik admin). */
    'auto_start_replacement_recruitment' => true,

    /** false = muthowif yang menerima langsung masuk pilihan jamaah; admin hanya memantau. */
    'require_admin_approval_for_candidates' => false,

    /** Minimal kandidat disetujui muthowif sebelum jamaah boleh memilih. */
    'min_candidates_to_open_customer_choice' => 1,

    /** Beri tahu jamaah WA setiap ada kandidat baru (false = hanya saat pilihan pertama dibuka). */
    'notify_customer_on_each_new_candidate' => false,

    /*
    | WhatsApp insiden (Fonnte) — aktif/nonaktif lewat .env:
    | FONNTE_INCIDENT_REPLACEMENT_OPPORTUNITY_NOTIFY_ENABLED  → muthowif lain
    | FONNTE_INCIDENT_CUSTOMER_REPLACEMENT_POOL_NOTIFY_ENABLED → jamaah (pool siap)
    */
];
