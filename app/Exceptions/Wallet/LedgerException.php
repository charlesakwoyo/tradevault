<?php

namespace App\Exceptions\Wallet;

use LogicException;

/** A posting that violates double-entry invariants. Always a programming error. */
class LedgerException extends LogicException {}
