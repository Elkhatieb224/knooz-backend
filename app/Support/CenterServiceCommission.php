<?php

namespace App\Support;

/**
 * Resolves app commission on a center↔service pivot as fixed SAR or percent of the operation amount.
 */
final class CenterServiceCommission
{
    public const MODE_FIXED = 'fixed';

    public const MODE_PERCENT = 'percent';

    /**
     * @return array<string, string>
     */
    public static function modeLabels(): array
    {
        return [
            self::MODE_FIXED => 'مبلغ ثابت',
            self::MODE_PERCENT => 'نسبة مئوية',
        ];
    }

    public static function normalizeMode(?string $mode): string
    {
        return $mode === self::MODE_PERCENT ? self::MODE_PERCENT : self::MODE_FIXED;
    }

    public static function commissionAmount(
        float $operationAmount,
        ?float $appProfit,
        ?string $mode = self::MODE_FIXED,
    ): float {
        if ($appProfit === null) {
            return 0.0;
        }

        $value = max(0.0, (float) $appProfit);

        if (self::normalizeMode($mode) === self::MODE_PERCENT) {
            $percent = min(100.0, $value);

            return round($operationAmount * ($percent / 100), 2);
        }

        return round($value, 2);
    }

    public static function netForCenter(
        float $operationAmount,
        ?float $appProfit,
        ?string $mode = self::MODE_FIXED,
        ?float $contractPrice = null,
    ): float {
        if ($appProfit !== null) {
            return max(0.0, round(
                $operationAmount - self::commissionAmount($operationAmount, $appProfit, $mode),
                2
            ));
        }

        if ($contractPrice !== null) {
            return (float) $contractPrice;
        }

        return $operationAmount;
    }

    public static function formatPivot(?float $appProfit, ?string $mode): string
    {
        if ($appProfit === null) {
            return '—';
        }

        if (self::normalizeMode($mode) === self::MODE_PERCENT) {
            return rtrim(rtrim(number_format((float) $appProfit, 2), '0'), '.').'%';
        }

        return number_format((float) $appProfit, 2).' ر.س';
    }
}
