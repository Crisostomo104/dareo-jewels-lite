<?php
declare(strict_types=1);

namespace App;

use InvalidArgumentException;

final class Money
{
    public static function cents(string $value): int
    {
        $value = trim($value);
        if (!preg_match('/^\d{1,10}(\.\d{1,2})?$/D', $value)) {
            throw new InvalidArgumentException('Invalid money amount.');
        }
        [$whole, $fraction] = array_pad(explode('.', $value), 2, '');
        return ((int) $whole) * 100 + (int) str_pad($fraction, 2, '0');
    }

    public static function show(int $cents): string
    {
        $negative = $cents < 0;
        $whole = intdiv(abs($cents), 100);
        $fraction = abs($cents) % 100;
        return ($negative ? '-₱' : '₱') . number_format($whole, 0) . '.' . str_pad((string) $fraction, 2, '0', STR_PAD_LEFT);
    }

    public static function input(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    public static function divideRounded(int $numerator, int $denominator): int
    {
        if ($denominator < 1) {
            throw new InvalidArgumentException('Denominator must be positive.');
        }
        return intdiv($numerator + intdiv($denominator, 2), $denominator);
    }
}
