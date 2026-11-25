<?php declare(strict_types=1);

namespace App\Exceptions\Auth;

use App\Exceptions\DomainException;

class TooManyLoginAttemptsException extends DomainException
{
    public function __construct(int $retryAfterSeconds)
    {
        parent::__construct(
            message: "Too many login attempts. Please try again in {$retryAfterSeconds} seconds.",
            errorCode: 'TOO_MANY_REQUESTS',
            statusCode: 429
        );
    }
}
