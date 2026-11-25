<?php declare(strict_types=1);

namespace App\Exceptions\Auth;

use App\Exceptions\DomainException;

class AccountSuspendedException extends DomainException
{
    public function __construct()
    {
        parent::__construct(
            message: 'Your account has been suspended. Please contact administration.',
            errorCode: 'ACCOUNT_SUSPENDED',
            statusCode: 403
        );
    }
}
