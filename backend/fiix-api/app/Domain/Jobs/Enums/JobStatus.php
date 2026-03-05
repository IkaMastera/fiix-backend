<?php

namespace App\Domain\Jobs\Enums;

enum JobStatus: string
{
    case SUBMITTED = 'submitted';
    case TRIAGED = 'triaged';
    case ASSIGNED = 'assigned';
    case IN_PROGRESS = 'in_progress';
    case BLOCKED = 'blocked';
    case DONE = 'done';
    case CANCELLED = 'canceled';
    case DISPUTED = 'disputed';

    public function isTerminal(): bool
    {
        return in_array($this, [self::DONE, self::CANCELLED, self::DISPUTED], true);
    }
}
