<?php

namespace Modules\ApiBridge\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CorsHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $allowedHosts = array_filter(array_map('trim', explode(',', (string) config('apibridge.cors_hosts'))));
        $origin = $request->headers->get('Origin');

        if ($allowedHosts) {
            if ($origin && $this->originAllowed($origin, $allowedHosts)) {
                $response->headers->set('Access-Control-Allow-Origin', $origin);
            }
        } else {
            $response->headers->set('Access-Control-Allow-Origin', '*');
        }

        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, X-Api-Key, X-ApiBridge-Key, Authorization');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');

        return $response;
    }

    protected function originAllowed(string $origin, array $allowed): bool
    {
        foreach ($allowed as $host) {
            if ($host === '*') {
                return true;
            }

            if (strcasecmp($host, $origin) === 0) {
                return true;
            }
        }

        return false;
    }
}



