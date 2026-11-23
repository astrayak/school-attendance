<?php

namespace App\Policies;

use App\Models\Pengguna;
use App\Models\SesiAbsensi;
use App\Services\LayananAbsensi;
use App\Services\LayananGuruPiket;

class SesiAbsensiPolicy
{
    public function viewAny(Pengguna $pengguna): bool
    {
        return in_array($pengguna->peran, ['admin', 'guru'], true);
    }

    public function view(Pengguna $pengguna, SesiAbsensi $sesi): bool
    {
        if ($pengguna->isAdmin()) {
            return true;
        }

        $guru = $pengguna->guru;

        if (! $guru) {
            return false;
        }

        if ($sesi->id_guru_penanggung_jawab === $guru->id_guru || $sesi->id_guru_pelaksana === $guru->id_guru) {
            return true;
        }

        return app(LayananGuruPiket::class)->adalahPiket($pengguna, $sesi->tanggal_pelajaran);
    }

    public function absen(Pengguna $pengguna, SesiAbsensi $sesi): bool
    {
        if (! $pengguna->isGuru()) {
            return false;
        }

        return app(LayananAbsensi::class)->bolehMengisi($sesi, $pengguna);
    }

    public function koreksi(Pengguna $pengguna, SesiAbsensi $sesi): bool
    {
        return $pengguna->isAdmin();
    }

    public function batalkan(Pengguna $pengguna, SesiAbsensi $sesi): bool
    {
        return $pengguna->isAdmin() && $sesi->catatanAbsensi()->doesntExist();
    }
}
