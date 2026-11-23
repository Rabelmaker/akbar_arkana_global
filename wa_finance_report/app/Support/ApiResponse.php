<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class ApiResponse
{
    public static function success(
        mixed $data = null,
        string $message = 'Request completed successfully.',
        array $meta = [],
        int $status = 200,
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => $meta === [] ? (object) [] : $meta,
        ], $status);
    }

    public static function error(
        string $message,
        array $errors = [],
        int $status = 400,
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors === [] ? (object) [] : $errors,
        ], $status);
    }

    /** @param class-string<JsonResource> $resource */
    public static function paginated(
        LengthAwarePaginator $paginator,
        string $resource,
        string $message = 'Resources retrieved successfully.',
    ): JsonResponse {
        return self::success(
            data: collect($paginator->items())
                ->map(fn ($item) => (new $resource($item))->resolve())
                ->values(),
            message: $message,
            meta: [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ],
        );
    }
}
