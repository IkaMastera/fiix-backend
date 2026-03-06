<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;

use App\Domain\Jobs\Exceptions\InvalidJobTransition;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // kept minimal for now
    })
    ->withExceptions(function (Exceptions $exceptions) {

        // Shared meta builder
        $meta = function (): array {
            return [
                'request_id' => null, // can be wired later via middleware
                'timestamp'  => now()->toIso8601String(),
            ];
        };

        // Shared error response builder
        $error = function (
            string $code,
            string $message,
            array $details = [],
            int $status = 500
        ) use ($meta) {
            return response()->json([
                'error' => [
                    'code'    => $code,
                    'message' => $message,
                    // keep details consistent:
                    // - empty -> {}
                    // - validation -> { field: [messages] }
                    'details' => $details ?: (object) [],
                ],
                'meta' => $meta(),
            ], $status);
        };

        // Validation → 422
        $exceptions->render(function (ValidationException $e) use ($error) {
            return $error(
                'validation_failed',
                'Validation failed.',
                $e->errors(),
                422
            );
        });

        // Unauthenticated → 401
        $exceptions->render(function (AuthenticationException $e) use ($error) {
            return $error(
                'unauthenticated',
                'Unauthenticated.',
                [],
                401
            );
        });

        // Forbidden → 403
        $exceptions->render(function (AuthorizationException $e) use ($error) {
            return $error(
                'forbidden',
                'Forbidden.',
                [],
                403
            );
        });

        // Not Found → 404
        $exceptions->render(function (ModelNotFoundException $e) use ($error) {
            return $error(
                'not_found',
                'Resource not found.',
                [],
                404
            );
        });

        // Rate limited → 429
        $exceptions->render(function (ThrottleRequestsException $e) use ($error) {
            return $error(
                'rate_limited',
                'Too many requests.',
                [],
                429
            );
        });

        // Domain invalid transition → 409
        $exceptions->render(function (InvalidJobTransition $e) use ($error) {
            $details = array_filter([
                'current_status'   => $e->current_status,
                'attempted_status' => $e->attempted_status,
            ], fn ($v) => $v !== null);
            return $error(
                'invalid_job_transition',
                $e->getMessage(),
                $details,
                409
            );
        });

        // Fallback → 500 (safe in prod, helpful in local, no sensitive information gets leaked)
        $exceptions->render(function (Throwable $e) use ($error) {
            $details = app()->environment('local')
                ? [
                    'exception' => get_class($e),
                    'message'   => $e->getMessage(),
                  ]
                : [];

            return $error(
                'server_error',
                'Unexpected error.',
                $details,
                500
            );
        });

    })
    ->create();