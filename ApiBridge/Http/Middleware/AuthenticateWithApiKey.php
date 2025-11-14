<?php

namespace Modules\ApiBridge\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\ApiBridge\Services\ApiKeyManager;

class AuthenticateWithApiKey
{
    protected ApiKeyManager $keys;

    public function __construct(ApiKeyManager $keys)
    {
        $this->keys = $keys;
    }

    public function handle(Request $request, Closure $next)
    {
        $provided = $request->header('X-Api-Key')
            ?: $request->header('X-ApiBridge-Key')
            ?: $this->bearerToken($request)
            ?: $request->query('api_key');

        if (!$this->keys->verify($provided)) {
            return response()->json([
                'message' => 'Invalid or missing API key.',
            ], 401);
        }

        return $next($request);
    }

    protected function bearerToken(Request $request): ?string
    {
        $authorization = $request->header('Authorization');

        if (!$authorization) {
            return null;
        }

        if (strpos($authorization, 'Bearer ') === 0) {
            return substr($authorization, 7);
        }

        return null;
    }
}



