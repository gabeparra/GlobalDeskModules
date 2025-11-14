<?php

namespace Modules\ApiBridge\Http\Controllers\Api;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\ApiBridge\Services\PayloadFormatter;

abstract class ApiController extends Controller
{
    protected PayloadFormatter $formatter;

    public function __construct(PayloadFormatter $formatter)
    {
        $this->formatter = $formatter;
    }

    protected function respond($data, int $status = 200, array $headers = []): JsonResponse
    {
        return response()->json($data, $status, $headers);
    }

    protected function respondWithPagination(LengthAwarePaginator $paginator, array $embedded): JsonResponse
    {
        return $this->respond([
            '_embedded' => $embedded,
            'page' => [
                'size' => (int) $paginator->perPage(),
                'totalElements' => (int) $paginator->total(),
                'totalPages' => (int) $paginator->lastPage(),
                'number' => (int) $paginator->currentPage(),
            ],
        ]);
    }

    protected function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->respond([
            'message' => $message,
        ], 404);
    }

    protected function badRequest(string $message, array $errors = []): JsonResponse
    {
        return $this->respond([
            'message' => $message,
            'errors' => $errors,
        ], 400);
    }
}



