<?php

return [
    /** Muthowif per batch broadcast otomatis. */
    'broadcast_batch_size' => (int) env('EMERGENCY_BROADCAST_BATCH_SIZE', 5),

    /** Maksimal batch otomatis sebelum admin wajib manual pick. */
    'max_auto_batches' => (int) env('EMERGENCY_MAX_AUTO_BATCHES', 10),
];
