<?php declare(strict_types=1);

namespace App\Exceptions;

class DomainValidationException extends DomainException
{
    /**
     * @param array<string, array<int, string>> $errors
     */
    public function __construct(string $message, array $errors = [])
    {
        parent::__construct($message, 'VALIDATION_ERROR', 422, $errors);
    }
}
