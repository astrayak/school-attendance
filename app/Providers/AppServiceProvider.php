<?php

namespace App\Providers;

use App\Models\JadwalPelajaran;
use App\Models\PermintaanKonfirmasi;
use App\Models\SesiAbsensi;
use App\Models\Siswa;
use App\Policies\JadwalPelajaranPolicy;
use App\Policies\PermintaanKonfirmasiPolicy;
use App\Policies\SesiAbsensiPolicy;
use App\Policies\SiswaPolicy;
use App\Services\LayananAbsensi;
use App\Services\LayananGuruPiket;
use App\Services\LayananJadwal;
use App\Services\LayananKonfirmasi;
use App\Services\LayananLogAktivitas;
use App\Services\LayananNotifikasi;
use App\Services\LayananPengaturan;
use App\Services\LayananPeriode;
use App\Services\LayananQrCode;
use App\Services\LayananRekapAbsensi;
use App\Services\LayananSesi;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LayananPengaturan::class);
        $this->app->singleton(LayananPeriode::class);
        $this->app->singleton(LayananLogAktivitas::class);
        $this->app->singleton(LayananNotifikasi::class);
        $this->app->singleton(LayananJadwal::class);
        $this->app->singleton(LayananQrCode::class);
        $this->app->singleton(LayananGuruPiket::class);
        $this->app->singleton(LayananKonfirmasi::class);
        $this->app->singleton(LayananRekapAbsensi::class);
        $this->app->singleton(LayananAbsensi::class);
        $this->app->singleton(LayananSesi::class);
    }

    public function boot(): void
    {
        Gate::policy(SesiAbsensi::class, SesiAbsensiPolicy::class);
        Gate::policy(PermintaanKonfirmasi::class, PermintaanKonfirmasiPolicy::class);
        Gate::policy(Siswa::class, SiswaPolicy::class);
        Gate::policy(JadwalPelajaran::class, JadwalPelajaranPolicy::class);
    }
}
