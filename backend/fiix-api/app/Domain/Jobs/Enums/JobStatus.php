<?php

namespace App\Domain\Jobs\Enums;

enum JobStatus: string
{
    case SUBMITTED = 'SUBMITTED';
    case TRIAGED = 'TRIAGED';
    case ASSIGNED = 'ASSIGNED';
    case IN_PROGRESS = 'IN_PROGRESS';
    case BLOCKED = 'BLOCKED';
    case DONE = 'DONE';
    case CANCELLED = 'CANCELLED';
    case DISPUTED = 'DISPUTED';

    public function isTerminal(): bool
    {
        return in_array($this, [self::DONE, self::CANCELLED, self::DISPUTED], true);
    }
}