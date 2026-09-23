<?php

namespace App\Exceptions\Wallet;

use App\Models\Wallet;
use RuntimeException;

class InsufficientFundsException extends RuntimeException
{
    public static function for(Wallet $wallet, string $required): self
    {
        return new self(sprintf(
            'Insufficient funds in %s wallet: %s %s required, %s available.',
            $wallet->type->value,
            $required,
            $wallet->currency,
            $wallet->balance,
        ));
    }
}
