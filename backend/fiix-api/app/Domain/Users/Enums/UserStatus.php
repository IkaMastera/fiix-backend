<?php

namespace App\Domain\Users\Enums;

enum UserStatus: string
{
    case ACTIVE = 'active';
    case BLOCKED = 'blocked';
    case PENDING_VERIFICATION = 'pending_verification';
}