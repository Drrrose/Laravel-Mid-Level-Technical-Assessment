<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class ApiExceptionRenderer
{
    /**
     * Render an exception into a JSON response if the request is an API request.
     */
    public function __invoke(Throwable $e, Request $request): ?JsonResponse
    {
        if (! $request->is('api/*')) {
            return null;
        }

        $statusCode = match (true) {
            $e instanceof ValidationException => 422,
            $e instanceof AuthenticationException => 401,
            $e instanceof AuthorizationException => 403,
            $e instanceof ModelNotFoundException => 404,
            $e instanceof HttpExceptionInterface => $e->getStatusCode(),
            default => 500,
        };

        $message = match (true) {
            $e instanceof AuthenticationException => $e->getMessage() ?: 'Unauthenticated.',
            $e instanceof AuthorizationException => 'This action is unauthorized.',
            $e instanceof ModelNotFoundException => 'Resource not found.',
            $e instanceof ValidationException => $e->getMessage(),
            default => (config('app.debug') && $e->getMessage()) ? $e->getMessage() : 'Server Error',
        };

        $response = [
            'status' => 'error',
            'message' => $message,
        ];

        if (method_exists($e, 'errors')) {
            $response['errors'] = $e->errors();
        }

        return response()->json($response, $statusCode);
    }
}
