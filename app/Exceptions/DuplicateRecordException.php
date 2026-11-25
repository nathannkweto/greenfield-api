<?php declare(strict_types=1);

namespace App\Exceptions;

class DuplicateRecordException extends DomainException
{
    /**
     * @param array<string, array<int, string>> $errors
     */
    public function __construct(
        string $message = 'A record with the provided unique details already exists.',
        array $errors = [],
        string $errorCode = 'DUPLICATE_RECORD',
        int $statusCode = 409,
    ) {
        parent::__construct(
            message: $message,
            errorCode: $errorCode,
            statusCode: $statusCode,
            errors: $errors,
        );
    }
}
