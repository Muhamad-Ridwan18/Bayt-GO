<?php

return [
    /** Muthowif per batch broadcast otomatis. */
    'broadcast_batch_size' => (int) env('EMERGENCY_BROADCAST_BATCH_SIZE', 5),

    /** Maksimal batch otomatis sebelum admin wajib manual pick. */
    'max_auto_batches' => (int) env('EMERGENCY_MAX_AUTO_BATCHES', 10),

    /**
     * Nomor WhatsApp admin yang mendapat notifikasi saat jamaah melaporkan insiden darurat.
     * Override via EMERGENCY_ADMIN_WHATSAPP_NUMBERS (koma-separated).
     *
     * @var list<string>
     */
    'admin_whatsapp_numbers' => array_values(array_filter(array_map(
        static fn (string $n): string => trim($n),
        explode(',', (string) env(
            'EMERGENCY_ADMIN_WHATSAPP_NUMBERS',
            '0881081871528,0812107021,081378731183',
        )),
    ), static fn (string $n): bool => $n !== '')),
];
