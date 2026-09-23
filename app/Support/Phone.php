<?php

namespace App\Support;

final class Phone
{
    /**
     * Normalize to E.164-like form (+ followed by digits) so the same number
     * cannot be registered twice with different formatting.
     */
    public static function normalize(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        return $digits === '' ? '' : '+'.$digits;
    }

    public static function mask(string $phone): string
    {
        $length = strlen($phone);

        return $length <= 6 ? $phone : substr($phone, 0, 4).str_repeat('•', $length - 7).substr($phone, -3);
    }
}
