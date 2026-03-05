<?php

namespace App\Domain\Jobs\Exceptions;

use RuntimeException;

final class InvalidJobTransition extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?string $current_status = null,
        public readonly ?string $attempted_status = null
    ) {
        parent::__construct($message);
    }
}
