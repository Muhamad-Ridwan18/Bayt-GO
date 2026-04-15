<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Penyelesaian otomatis setelah hari layanan terakhir
    |--------------------------------------------------------------------------
    |
    | Setelah tanggal ends_on lewat (hari kalender berikutnya), sistem dapat
    | menandai layanan selesai tanpa klik customer. Nilai di bawah menambah
    | jeda setelah awal hari tersebut (00:00 zona APP_TIMEZONE), mis. 1 = satu
    | menit setelah tengah malam hari berikutnya.
    |
    */
    'auto_complete_grace_minutes_after_service_day' => max(0, (int) env('BOOKING_AUTO_COMPLETE_GRACE_MINUTES_AFTER_DAY', 0)),

    /*
    | Rating default (1–5) saat penyelesaian otomatis (tanpa input jamaah).
    */
    'auto_complete_default_rating' => min(5, max(1, (int) env('BOOKING_AUTO_COMPLETE_DEFAULT_RATING', 5))),

];
