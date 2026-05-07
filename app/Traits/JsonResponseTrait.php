<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait JsonResponseTrait
{
    /**
     * Success response.
     */
    protected function success(
        mixed $data = null,
        string $message = 'Success',
        int $code = 200,
        mixed $meta = null
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => null,
            'meta' => $meta,
        ], $code);
    }

    /**
     * Error response.
     */
    protected function error(
        string $message = 'Error',
        int $code = 400,
        mixed $errors = null,
        mixed $meta = null
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => $errors,
            'meta' => $meta,
        ], $code);
    }
}
