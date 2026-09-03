<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

final class PerformanceTelemetry
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! (bool) config('performance.enabled')) {
            return $next($request);
        }

        $started = hrtime(true);
        $response = $next($request);
        $elapsed = (int) ceil((hrtime(true) - $started) / 1_000_000);

        Log::info('http_performance', [
            'method' => $request->method(),
            'path' => '/'.ltrim($request->path(), '/'),
            'status' => $response->getStatusCode(),
            'latency_ms' => $elapsed,
            'response_bytes' => strlen((string) $response->getContent()),
        ]);

        return $response;
    }
}
