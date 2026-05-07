<?php

namespace App\Http\Controllers\Health;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class HealthController extends Controller
{
    public function __invoke()
    {
        $checks = [
            'db' => $this->checkDb(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
        ];

        $ok = collect($checks)->every(fn ($status) => $status === 'ok');

        return response()->json([
            'success' => $ok,
            'message' => $ok ? 'OK' : 'DEGRADED',
            'data' => ['checks' => $checks],
            'errors' => null,
            'meta' => null,
        ], $ok ? 200 : 503);
    }

    private function checkDb(): string
    {
        try {
            DB::select('select 1');

            return 'ok';
        } catch (\Throwable) {
            return 'fail';
        }
    }

    private function checkCache(): string
    {
        try {
            Cache::put('health_check_probe', '1', 10);

            return Cache::get('health_check_probe') === '1' ? 'ok' : 'fail';
        } catch (\Throwable) {
            return 'fail';
        }
    }

    private function checkQueue(): string
    {
        try {
            // Lightweight probe: validates queue connection is reachable.
            Queue::size();

            return 'ok';
        } catch (\Throwable) {
            return 'fail';
        }
    }
}
