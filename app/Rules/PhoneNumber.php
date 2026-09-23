<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * International phone number in E.164 form (+ and 8–15 digits).
 * Swap for libphonenumber when stricter per-country validation is needed.
 */
class PhoneNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/^\+[1-9]\d{7,14}$/', $value)) {
            $fail(__('Enter a valid phone number in international format, e.g. +254712345678.'));
        }
    }
}
