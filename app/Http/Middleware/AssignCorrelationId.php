<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class AssignCorrelationId
{
    private const HEADER = 'X-Correlation-ID';

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $supplied = strtolower(trim((string) $request->header(self::HEADER)));
        $correlationId = preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[47][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D', $supplied) === 1
            ? $supplied
            : (string) Str::uuid7();

        $request->attributes->set('correlation_id', $correlationId);

        return Context::scope(function () use ($request, $next, $correlationId): Response {
            $response = $next($request);
            $response->headers->set(self::HEADER, $correlationId);

            return $response;
        }, [
            'correlation_id' => $correlationId,
        ]);
    }
}
