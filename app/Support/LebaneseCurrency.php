<?php

namespace App\Support;

class LebaneseCurrency
{
    public static function format(float|int|string|null $amount): string
    {
        return 'LBP ' . number_format((float) ($amount ?? 0), 0);
    }
}
