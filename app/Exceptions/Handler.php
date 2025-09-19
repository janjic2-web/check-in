use Illuminate\Http\Exceptions\ThrottleRequestsException;

class Handler extends ExceptionHandler
{
    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e): JsonResponse
    {
        $reqId = $request->headers->get('X-Request-Id') ?? (string) Str::uuid();

        // Custom handler for rate limiting
        if ($e instanceof ThrottleRequestsException) {
            return response()->json([
                'error' => [
                    'code' => 'RATE_LIMITED',
                    'message' => $e->getMessage() ?: 'Rate limit exceeded',
                ],
                'request_id' => $reqId,
            ], 429)->header('X-Request-Id', $reqId);
        }
<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Illuminate\Support\Str;

class Handler extends ExceptionHandler
{
    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e): JsonResponse
    {
        $reqId = $request->headers->get('X-Request-Id') ?? (string) Str::uuid();

        // Validation errors
        if ($e instanceof ValidationException) {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_FAILED',
                    'message' => $e->getMessage(),
                    'details' => $e->errors(),
                ]
            ], 422)
            ->header('X-Request-Id', $reqId);
        }

        // Auth errors
        if ($e instanceof AuthenticationException) {
            return response()
                ->json([
                    'error' => [
                        'code' => 'UNAUTHORIZED',
                        'message' => $e->getMessage(),
                    ],
                    'request_id' => $reqId,
                ], 401)
                ->header('X-Request-Id', $reqId);
        }
        if ($e instanceof AuthorizationException) {
            return response()
                ->json([
                    'error' => [
                        'code' => 'FORBIDDEN',
                        'message' => $e->getMessage(),
                    ],
                    'request_id' => $reqId,
                ], 403)
                ->header('X-Request-Id', $reqId);
        }

        // HTTP exceptions
        if ($e instanceof HttpExceptionInterface) {
            $status = $e->getStatusCode();
            $code = match ($status) {
                400 => 'BAD_REQUEST',
                401 => 'UNAUTHORIZED',
                403 => 'FORBIDDEN',
                404 => 'NOT_FOUND',
                409 => 'CONFLICT',
                422 => 'UNPROCESSABLE_ENTITY',
                429 => 'RATE_LIMITED',
                default => 'ERROR',
            };
            return response()
                ->json([
                    'error' => [
                        'code' => $code,
                        'message' => $e->getMessage(),
                    ],
                    'request_id' => $reqId,
                ], $status)
                ->header('X-Request-Id', $reqId);
        }

        // Fallback: Internal error
        return response()
            ->json([
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => $e->getMessage(),
                ],
                'request_id' => $reqId,
            ], 500)
            ->header('X-Request-Id', $reqId);
    }
}
