<?php

return [
    /**
     * Ambang penilaian editorial untuk artikel hasil automation.
     *
     * min_score            : di bawah ini artikel diblokir dan dikembalikan untuk revisi.
     * autopublish_min_score: pada atau di atas ini artikel langsung terbit.
     * Di antara keduanya artikel disimpan sebagai draft untuk direview manual.
     */
    'qa' => [
        'min_score' => (int) env('ARTICLE_QA_MIN_SCORE', 75),
        'autopublish_min_score' => (int) env('ARTICLE_QA_AUTOPUBLISH_MIN_SCORE', 85),
    ],
];
