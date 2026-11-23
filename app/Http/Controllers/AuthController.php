<?php

namespace App\Http\Controllers;

use App\Models\Pengguna;
use App\Services\LayananLogAktivitas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(private readonly LayananLogAktivitas $log) {}

    public function tampilkanLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $kredensial = $request->validate([
            'email' => ['required', 'email', 'max:150'],
            'kata_sandi' => ['required', 'string', 'min:6'],
        ]);

        $kunci = Str::lower($kredensial['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($kunci, 5)) {
            $detik = RateLimiter::availableIn($kunci);

            throw ValidationException::withMessages([
                'email' => "Terlalu banyak percobaan login. Coba lagi dalam {$detik} detik.",
            ]);
        }

        // Laravel memakai kunci "password" pada kredensial untuk divalidasi
        // terhadap kolom yang dikembalikan getAuthPasswordName() (kata_sandi).
        $berhasil = Auth::attempt([
            'email' => $kredensial['email'],
            'password' => $kredensial['kata_sandi'],
            'status_aktif' => true,
        ], $request->boolean('ingat_saya'));

        if (! $berhasil) {
            RateLimiter::hit($kunci, 60);

            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi salah, atau akun tidak aktif.',
            ]);
        }

        RateLimiter::clear($kunci);
        $request->session()->regenerate();

        /** @var Pengguna $pengguna */
        $pengguna = Auth::user();
        $pengguna->forceFill(['terakhir_login' => now()])->save();

        $this->log->catat(
            $pengguna,
            'login',
            'Autentikasi',
            "Pengguna {$pengguna->nama_pengguna} login sebagai {$pengguna->peran}."
        );

        return redirect()->route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        $pengguna = Auth::user();

        if ($pengguna instanceof Pengguna) {
            $this->log->catat(
                $pengguna,
                'logout',
                'Autentikasi',
                "Pengguna {$pengguna->nama_pengguna} logout."
            );
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('sukses', 'Anda berhasil keluar.');
    }
}
