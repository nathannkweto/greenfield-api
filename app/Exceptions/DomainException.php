<?php declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

abstract class DomainException extends Exception
{
    /**
     * @param array<string, array<int, string>> $errors
     */
    public function __construct(
        string $message,
        public readonly string $errorCode,
        public readonly int $statusCode = 400,
        public readonly array $errors = [],
    ) {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        $payload = [
            'message' => $this->getMessage(),
            'error' => $this->errorCode,
        ];

        if (!empty($this->errors)) {
            $payload['errors'] = $this->errors;
        }

        return response()->json($payload, $this->statusCode);
    }
}
