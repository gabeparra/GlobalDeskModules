<?php

namespace Modules\ApiBridge\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApiBridge\Services\PayloadFormatter;

class CorsController extends ApiController
{
    public function __construct(PayloadFormatter $formatter)
    {
        parent::__construct($formatter);
    }

    public function handle(Request $request): JsonResponse
    {
        return $this->respond([], 204);
    }
}


