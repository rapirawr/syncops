<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class TrackMetricsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Example routing in the target app's routes/web.php or routes/api.php:
     *
     * Route::get('/metrics', function () {
     *     $bucket = floor(time() / 300) * 300;
     *     // Pull previous 5-min bucket for stable numbers, fallback to current
     *     $prevBucket = $bucket - 300;
     * 
     *     $requests = Cache::get("metrics:{$prevBucket}:requests_count") 
     *         ?? Cache::get("metrics:{$bucket}:requests_count") ?? 0;
     *     $errors = Cache::get("metrics:{$prevBucket}:errors_count") 
     *         ?? Cache::get("metrics:{$bucket}:errors_count") ?? 0;
     *     $totalTime = Cache::get("metrics:{$prevBucket}:total_response_time_ms") 
     *         ?? Cache::get("metrics:{$bucket}:total_response_time_ms") ?? 0;
     * 
     *     $avgTime = $requests > 0 ? (int)($totalTime / $requests) : 0;
     * 
     *     return response()->json([
     *         'requests_count' => (int)$requests,
     *         'errors_count' => (int)$errors,
     *         'avg_response_time_ms' => $avgTime,
     *         'period' => 'last_5_minutes'
     *     ]);
     * });
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        // Skip recording metrics for the metrics endpoint itself
        if ($request->is('metrics')) {
            return $response;
        }

        $durationMs = (int)((microtime(true) - $startTime) * 1000);
        $bucket = floor(time() / 300) * 300;

        try {
            // Increment request count
            Cache::remember("metrics:{$bucket}:requests_count", 600, fn() => 0);
            Cache::increment("metrics:{$bucket}:requests_count");

            // Increment total response time
            Cache::remember("metrics:{$bucket}:total_response_time_ms", 600, fn() => 0);
            Cache::increment("metrics:{$bucket}:total_response_time_ms", $durationMs);

            // Increment error counts if code is >= 400
            if ($response->getStatusCode() >= 400) {
                Cache::remember("metrics:{$bucket}:errors_count", 600, fn() => 0);
                Cache::increment("metrics:{$bucket}:errors_count");
            }
        } catch (\Exception $e) {
            // Suppress caching errors to ensure app runtime is not impacted
        }

        return $response;
    }
}
