<?php

namespace App\Domain\Users\Enums;

enum UserRole: string
{
    case CUSTOMER = 'customer';
    case TECHNICIAN = 'technician';
    case OPERATOR = 'operator';
    case ADMIN = 'admin';
}