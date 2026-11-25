<?php declare(strict_types=1);

namespace App\Exceptions\Student;

use App\Exceptions\DomainException;

class InvalidStudentStateException extends DomainException
{
    public function __construct(string $action, string $currentStatus, string $expectedStatus)
    {
        parent::__construct(
            message: "Cannot {$action} student with status '{$currentStatus}'. Expected '{$expectedStatus}'.",
            errorCode: 'INVALID_STATE_TRANSITION',
            statusCode: 409
        );
    }
}
