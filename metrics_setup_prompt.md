# Prompt / Panduan Implementasi Telemetri `/metrics` di Laravel Target

Salin dan gunakan prompt/instruksi di bawah ini untuk asisten AI Anda saat ingin memasang fitur pemantauan status (telemetri) pada project target yang ingin dipantau oleh Dashboard Monitoring.

---

### 📋 COPY PROMPT DI BAWAH INI:

```text
Buatkan fitur telemetri sederhana untuk memantau request, error, dan response time (latency) pada aplikasi Laravel ini agar bisa dipantau oleh dashboard monitoring external.

Implementasikan langkah-langkah berikut:

1. Buat Middleware `TrackMetricsMiddleware` yang berfungsi untuk:
   - Mencatat total request masuk (`requests_count`).
   - Mencatat request yang menghasilkan error status code >= 400 (`errors_count`).
   - Mengukur durasi/response time (latency) dalam milidetik (ms) dan mencatat total akumulasi waktunya (`total_response_time_ms`).
   - Simpan data-data tersebut di Cache (Redis/Database/File) menggunakan key berbasis "5-minute bucket" (floor timestamp ke kelipatan 300 detik) agar data otomatis terkelompok per 5 menit dan tidak membebani database utama.
   - Pastikan middleware tidak mencatat request yang masuk ke endpoint `/metrics` itu sendiri agar tidak mengotori data.

2. Daftarkan Middleware tersebut di `bootstrap/app.php` (untuk Laravel 11+) atau `app/Http/Kernel.php` (untuk Laravel 10 kebawah) agar berjalan secara global pada grup `web` dan `api`.

3. Buat Route `GET /metrics` yang akan mengembalikan JSON statistik pemantauan. Route ini harus:
   - Mengambil data dari cache untuk bucket 5 menit terakhir yang sudah selesai (static), atau fallback ke bucket saat ini jika data sebelumnya kosong.
   - Menghitung rata-rata response time: `avg_response_time_ms = total_response_time_ms / requests_count`.
   - Mengembalikan response JSON dengan struktur format berikut:
     {
       "requests_count": 120,
       "errors_count": 2,
       "avg_response_time_ms": 145,
       "period": "last_5_minutes"
     }
   - (Opsional) Berikan mekanisme pengamanan sederhana menggunakan Token/API Key yang dikirim lewat header (misalnya `X-Metrics-Token`), yang dibaca dari config/env (.env `METRICS_TOKEN=your-secure-token`) agar endpoint /metrics tidak bisa diakses sembarangan oleh publik.
```

---

## 🛠️ Contoh Implementasi Kode Hasil Prompt

### 1. Middleware (`app/Http/Middleware/TrackMetricsMiddleware.php`)
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class TrackMetricsMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        // Jangan catat endpoint /metrics
        if ($request->is('metrics')) {
            return $response;
        }

        $durationMs = (int)((microtime(true) - $startTime) * 1000);
        $bucket = floor(time() / 300) * 300;

        try {
            Cache::remember("metrics:{$bucket}:requests_count", 600, fn() => 0);
            Cache::increment("metrics:{$bucket}:requests_count");

            Cache::remember("metrics:{$bucket}:total_response_time_ms", 600, fn() => 0);
            Cache::increment("metrics:{$bucket}:total_response_time_ms", $durationMs);

            if ($response->getStatusCode() >= 400) {
                Cache::remember("metrics:{$bucket}:errors_count", 600, fn() => 0);
                Cache::increment("metrics:{$bucket}:errors_count");
            }
        } catch (\Exception $e) {
            // Biarkan lewat agar tidak mengganggu jalannya aplikasi jika cache error
        }

        return $response;
    }
}
```

### 2. Route & Controller / Closure (`routes/web.php` atau `routes/api.php`)
```php
use Illuminate\Support\Facades\Route;
use Illuminate\Support5\Facades\Cache;
use Illuminate\Http\Request;

Route::get('/metrics', function (Request $request) {
    // Pengamanan opsional menggunakan token header
    $expectedToken = env('METRICS_TOKEN');
    if ($expectedToken && $request->header('X-Metrics-Token') !== $expectedToken) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $bucket = floor(time() / 300) * 300;
    $prevBucket = $bucket - 300; // Ambil data 5 menit lalu agar datanya utuh/stabil

    $requests = Cache::get("metrics:{$prevBucket}:requests_count") 
        ?? Cache::get("metrics:{$bucket}:requests_count") ?? 0;
    $errors = Cache::get("metrics:{$prevBucket}:errors_count") 
        ?? Cache::get("metrics:{$bucket}:errors_count") ?? 0;
    $totalTime = Cache::get("metrics:{$prevBucket}:total_response_time_ms") 
        ?? Cache::get("metrics:{$bucket}:total_response_time_ms") ?? 0;

    $avgTime = $requests > 0 ? (int)($totalTime / $requests) : 0;

    return response()->json([
        'requests_count' => (int)$requests,
        'errors_count' => (int)$errors,
        'avg_response_time_ms' => $avgTime,
        'period' => 'last_5_minutes'
    ]);
});
```
