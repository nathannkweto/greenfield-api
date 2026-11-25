<?php declare(strict_types=1);

namespace App\Exceptions;

class ResourceNotFoundException extends DomainException
{
    public function __construct(string $resource, string $identifier = '')
    {
        $message = $identifier !== ''
            ? "{$resource} '{$identifier}' not found."
            : "{$resource} not found.";

        parent::__construct($message, 'NOT_FOUND', 404);
    }
}
