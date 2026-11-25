<?php declare(strict_types=1);

namespace App\Exceptions\Student;

use App\Exceptions\DomainException;

class DuplicateStudentException extends DomainException
{
    public function __construct(string $field)
    {
        parent::__construct(
            message: "A student with this {$field} already exists.",
            errorCode: 'DUPLICATE_RECORD',
            statusCode: 409,
            errors: [$field => ["The {$field} has already been taken."]]
        );
    }
}
