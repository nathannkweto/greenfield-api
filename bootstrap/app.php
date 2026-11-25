<?php

use App\Exceptions\DomainException;
use Crell\Serde\SerdeException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Enable stateful session processing on API endpoints
        $middleware->statefulApi();

        $middleware->validateCsrfTokens(except: [
            'graphql',
        ]);

        // Custom Role Middleware Alias
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $isApiRequest = fn (Request $request): bool =>
            $request->is('api/*') || $request->expectsJson();

        // 1. Custom Domain Exceptions (404, 409, 422, etc.)
        $exceptions->render(function (DomainException $e, Request $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return $e->render();
            }
        });

        // 2. OpenAPI / Crell Serde Deserialization Errors (bad JSON types or enum values)
        $exceptions->render(function (SerdeException $e, Request $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json([
                    'message' => 'Invalid request payload format.',
                    'error' => 'VALIDATION_ERROR',
                    'errors' => ['payload' => [$e->getMessage()]],
                ], 422);
            }
        });

        // 3. Laravel Validation Exceptions
        $exceptions->render(function (ValidationException $e, Request $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'error' => 'VALIDATION_ERROR',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        // 4. Authentication & Authorization
        $exceptions->render(function (AuthenticationException $e, Request $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json([
                    'message' => 'Unauthenticated.',
                    'error' => 'UNAUTHENTICATED',
                ], 401);
            }
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'You do not have permission to perform this action.',
                    'error' => 'FORBIDDEN',
                ], 403);
            }
        });

        // 5. Database Integrity / Unique Constraint Violations (SQLSTATE 23000)
        $exceptions->render(function (QueryException $e, Request $request) use ($isApiRequest) {
            if ($isApiRequest($request) && (string) $e->getCode() === '23000') {
                return response()->json([
                    'message' => 'A record with the provided unique details already exists or violates a data constraint.',
                    'error' => 'CONFLICT',
                ], 409);
            }
        });

        // 6. Standard HTTP Exceptions (404 Route Not Found, 405 Method Not Allowed, etc.)
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'HTTP error occurred.',
                    'error' => 'HTTP_' . $e->getStatusCode(),
                ], $e->getStatusCode());
            }
        });

        $exceptions->render(function (ThrottleRequestsException $e, Request $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json([
                    'message' => 'Too many requests. Please slow down and try again later.',
                    'error' => 'TOO_MANY_REQUESTS',
                ], 429);
            }
        });
    })
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['middleware' => ['web', 'auth:sanctum']]
    )->create();
