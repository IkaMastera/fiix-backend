<?php

namespace App\Domain\Jobs\Enums;

enum JobUrgency: string
{
    case LOW = 'low';
    case NORMAL = 'normal';
    case HIGH = 'high';
    case EMERGENCY = 'emergency';
}