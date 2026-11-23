<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PastikanPeran
{
    public function handle(Request $request, Closure $next, string ...$peran): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $pengguna = Auth::user();

        if (! $pengguna->status_aktif) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Akun Anda tidak aktif. Hubungi administrator.',
            ]);
        }

        if (! in_array($pengguna->peran, $peran, true)) {
            abort(403, 'Anda tidak memiliki hak akses ke halaman ini.');
        }

        return $next($request);
    }
}
