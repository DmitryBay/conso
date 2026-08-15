<?php

namespace App\Support;

class Money
{
    public function format(int $minor, string $currency): string
    {
        if ($currency === 'IDR') {
            return 'Rp '.number_format($minor, 0, ',', '.');
        }

        $amount = $minor / 100;

        return match ($currency) {
            'USD' => '$'.number_format($amount, 2, '.', ','),
            'EUR' => '€'.number_format($amount, 2, '.', ','),
            'AUD' => 'A$'.number_format($amount, 2, '.', ','),
            'GBP' => '£'.number_format($amount, 2, '.', ','),
            'AED' => 'AED '.number_format($amount, 2, '.', ','),
            'CNY' => '¥'.number_format($amount, 2, '.', ','),
            'KRW' => '₩'.number_format($amount, 0, '.', ','),
            default => number_format($amount, 2, '.', ',').' '.$currency,
        };
    }

    public function approximateUsd(int $minor, string $currency): ?string
    {
        if ($currency === 'USD') {
            return null;
        }

        $rate = (float) config('concierge.usd_rates.'.$currency, 0);
        if ($rate <= 0) {
            return null;
        }

        $baseAmount = $currency === 'IDR' ? $minor : $minor / 100;

        return '≈ $'.number_format($baseAmount / $rate, 2, '.', ',');
    }
}
