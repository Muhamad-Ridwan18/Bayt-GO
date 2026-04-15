<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Batas pengajuan refund & reschedule (hari kalender sebelum mulai layanan)
    |--------------------------------------------------------------------------
    */

    'refund_min_days_before_service' => (int) env('BOOKING_REFUND_MIN_DAYS_BEFORE_SERVICE', 60),

    'reschedule_min_days_before_service' => (int) env('BOOKING_RESCHEDULE_MIN_DAYS_BEFORE_SERVICE', 30),

];
