<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin finance — riwayat transaksi (timeline)
    |--------------------------------------------------------------------------
    |
    | Hanya event dalam rentang bulan ini yang dimuat untuk tabel riwayat.
    | Agregat kartu (total fee platform, bruto) tetap dari seluruh data.
    |
    */
    'finance' => [
        'history_months' => max(1, min(120, (int) env('ADMIN_FINANCE_HISTORY_MONTHS', 24))),
    ],

];
