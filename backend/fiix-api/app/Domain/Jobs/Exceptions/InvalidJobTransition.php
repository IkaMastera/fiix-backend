<?php

namespace App\Domain\Jobs\Exceptions;

user RuntimeException;

final class InvalidJobTransition extends RuntimeException
{
}