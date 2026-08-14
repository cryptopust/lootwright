<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

class ReadinessController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->databaseIsReady(),
            'redis' => $this->redisIsReady(),
        ];
        $ready = ! in_array(false, $checks, true);

        return response()->json(
            [
                'status' => $ready ? 'ready' : 'unavailable',
                'checks' => array_map(
                    static fn (bool $check): string => $check ? 'ok' : 'failed',
                    $checks,
                ),
            ],
            $ready ? 200 : 503,
        );
    }

    private function databaseIsReady(): bool
    {
        try {
            DB::select('select 1');

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function redisIsReady(): bool
    {
        try {
            Redis::command('ping');

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
