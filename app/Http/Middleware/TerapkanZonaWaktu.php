<?php

namespace App\Http\Middleware;

use App\Services\LayananPengaturan;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TerapkanZonaWaktu
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $zona = Cache::remember('zona_waktu_aplikasi', 600, function () {
                return app(LayananPengaturan::class)->zonaWaktu();
            });
        } catch (Throwable) {
            $zona = config('app.timezone', 'Asia/Makassar');
        }

        if (is_string($zona) && in_array($zona, timezone_identifiers_list(), true)) {
            config(['app.timezone' => $zona]);
            date_default_timezone_set($zona);
        }

        return $next($request);
    }
}
