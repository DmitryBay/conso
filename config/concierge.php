<?php

return [
    // Amount of each currency equal to approximately one USD. Override in production from a rate provider.
    'usd_rates' => [
        'IDR' => (float) env('USD_IDR_RATE', 16500),
        'EUR' => (float) env('USD_EUR_RATE', 0.92),
        'GBP' => (float) env('USD_GBP_RATE', 0.78),
        'AED' => 3.6725,
        'CNY' => (float) env('USD_CNY_RATE', 7.20),
        'KRW' => (float) env('USD_KRW_RATE', 1380),
        'USD' => 1,
    ],
];
