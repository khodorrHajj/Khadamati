<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class LebaneseCurrency
{
    private const FALLBACK_RATE = 89500.0;

    public static function format(float|int|string|null $amount): string
    {
        return 'LBP ' . number_format((float) ($amount ?? 0), 0);
    }

    public static function getRate(): float
    {
        return Cache::remember('usd_lbp_rate', 86400, function () {
            try {
                $response = Http::timeout(5)->get('https://open.er-api.com/v6/latest/USD');

                if ($response->successful() && $response->json('result') === 'success') {
                    $rate = (float) $response->json('rates.LBP');

                    if ($rate > 0) {
                        return $rate;
                    }
                }
            } catch (\Exception) {}

            return self::FALLBACK_RATE;
        });
    }

    public static function toUsd(float $lbp): float
    {
        return round($lbp / self::getRate(), 2);
    }
}
