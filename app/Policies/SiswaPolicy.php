<?php

namespace App\Policies;

use App\Models\Pengguna;
use App\Models\Siswa;

class SiswaPolicy
{
    public function viewAny(Pengguna $pengguna): bool
    {
        return in_array($pengguna->peran, ['admin', 'guru'], true);
    }

    public function view(Pengguna $pengguna, Siswa $siswa): bool
    {
        if ($pengguna->isAdmin()) {
            return true;
        }

        return $pengguna->siswa?->id_siswa === $siswa->id_siswa;
    }

    public function update(Pengguna $pengguna, Siswa $siswa): bool
    {
        return $pengguna->isAdmin();
    }
}
