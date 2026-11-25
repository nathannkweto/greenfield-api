<?php declare(strict_types=1);

namespace App\Exceptions\Auth;

use App\Exceptions\DomainException;

class InvalidCredentialsException extends DomainException
{
    public function __construct()
    {
        parent::__construct(
            message: 'The provided credentials do not match our records.',
            errorCode: 'INVALID_CREDENTIALS',
            statusCode: 401
        );
    }
}
