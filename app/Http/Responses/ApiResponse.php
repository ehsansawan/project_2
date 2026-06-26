<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success(
        mixed  $data = null,
        string $message = 'Success',
        int    $code = 200
    ): JsonResponse
    {
        return response()->json([
            'status' => 1,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    public static function error(
        array  $errors = [],
        string $message = 'Something went wrong',
        int    $code = 500
    ): JsonResponse
    {
        return response()->json([
            'status' => 0,
            'message' => $message,
            'errors' => empty($errors)
                ? ['general' => [$message]]
                : $errors,
        ], $code);
    }

    public static function validation(
        array  $errors,
        string $message = 'Validation failed',
        int    $code = 422
    ): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }

}


