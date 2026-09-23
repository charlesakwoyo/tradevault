<?php

namespace App\Rules;

use App\Support\Countries;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Enforces the geographic restrictions configured in config/tradevault.php.
 */
class AllowedCountry implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $code = is_string($value) ? strtoupper($value) : '';

        if (! array_key_exists($code, Countries::all())) {
            $fail(__('Select a valid country.'));

            return;
        }

        if (! Countries::isPermitted($code)) {
            $fail(__('We are not able to offer our services to residents of this country.'));
        }
    }
}
