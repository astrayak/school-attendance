<?php

namespace App\Policies;

use App\Models\PermintaanKonfirmasi;
use App\Models\Pengguna;

class PermintaanKonfirmasiPolicy
{
    public function view(Pengguna $pengguna, PermintaanKonfirmasi $permintaan): bool
    {
        if ($pengguna->isAdmin()) {
            return true;
        }

        return $pengguna->siswa?->id_siswa === $permintaan->id_siswa_penerima;
    }

    public function jawab(Pengguna $pengguna, PermintaanKonfirmasi $permintaan): bool
    {
        return $permintaan->status_permintaan === PermintaanKonfirmasi::MENUNGGU
            && $pengguna->siswa?->id_siswa === $permintaan->id_siswa_penerima;
    }
}
