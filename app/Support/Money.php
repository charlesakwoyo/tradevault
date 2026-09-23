<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Arbitrary-precision decimal arithmetic for monetary amounts.
 *
 * Amounts are passed around as numeric strings (matching the DECIMAL(24,8)
 * columns) and never as floats, so no rounding error ever reaches the ledger.
 */
final class Money
{
    public const SCALE = 8;

    public static function normalize(string|int|float $amount): string
    {
        if (is_float($amount)) {
            // Floats are only accepted from trusted code (e.g. config); format them exactly.
            $amount = number_format($amount, self::SCALE, '.', '');
        }

        $amount = trim((string) $amount);

        if (! preg_match('/^-?\d+(\.\d+)?$/', $amount)) {
            throw new InvalidArgumentException("Invalid monetary amount [{$amount}].");
        }

        return bcadd($amount, '0', self::SCALE);
    }

    public static function add(string $a, string $b): string
    {
        return bcadd(self::normalize($a), self::normalize($b), self::SCALE);
    }

    public static function sub(string $a, string $b): string
    {
        return bcsub(self::normalize($a), self::normalize($b), self::SCALE);
    }

    public static function mul(string $a, string $b): string
    {
        return bcmul(self::normalize($a), self::normalize($b), self::SCALE);
    }

    public static function div(string $a, string $b): string
    {
        if (self::isZero($b)) {
            throw new InvalidArgumentException('Division by zero.');
        }

        return bcdiv(self::normalize($a), self::normalize($b), self::SCALE);
    }

    public static function negate(string $a): string
    {
        return bcmul(self::normalize($a), '-1', self::SCALE);
    }

    public static function compare(string $a, string $b): int
    {
        return bccomp(self::normalize($a), self::normalize($b), self::SCALE);
    }

    public static function isZero(string $a): bool
    {
        return self::compare($a, '0') === 0;
    }

    public static function isPositive(string $a): bool
    {
        return self::compare($a, '0') > 0;
    }

    public static function isNegative(string $a): bool
    {
        return self::compare($a, '0') < 0;
    }

    /** @param  iterable<string>  $amounts */
    public static function sum(iterable $amounts): string
    {
        $total = '0';

        foreach ($amounts as $amount) {
            $total = self::add($total, $amount);
        }

        return self::normalize($total);
    }

    public static function format(string|int|float|null $amount, string $currency = '', int $decimals = 2): string
    {
        $value = number_format((float) ($amount ?? 0), $decimals);

        return $currency === '' ? $value : "{$currency} {$value}";
    }
}
