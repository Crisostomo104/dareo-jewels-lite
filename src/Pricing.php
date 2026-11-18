<?php
declare(strict_types=1);

namespace App;

final class Pricing
{
    /** Profit MARGIN on selling price, plus optional installment markup. Integer centavos. */
    public static function quote(int $costCents, float $marginPercent, float $markupPercent): array
    {
        if ($costCents < 1 || $marginPercent < 0 || $marginPercent >= 100 || $markupPercent < 0 || $markupPercent > 100) {
            throw new \InvalidArgumentException('Invalid pricing inputs.');
        }
        $cash = Money::divideRounded($costCents * 100, (int) round(100 - $marginPercent));
        $installment = Money::divideRounded((int) round($cash * (100 + $markupPercent)), 100);
        return [
            'cash' => $cash,
            'installment' => $installment,
            'cash_profit' => $cash - $costCents,
            'installment_profit' => $installment - $costCents,
        ];
    }

    public static function settings(): array
    {
        return [
            'margin' => (float) Database::setting('target_profit_percent', '25'),
            'markup' => (float) Database::setting('installment_markup_percent', '15'),
        ];
    }
}
