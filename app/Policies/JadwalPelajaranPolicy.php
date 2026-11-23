<?php

namespace App\Policies;

use App\Models\JadwalPelajaran;
use App\Models\Pengguna;

class JadwalPelajaranPolicy
{
    public function viewAny(Pengguna $pengguna): bool
    {
        return in_array($pengguna->peran, ['admin', 'guru'], true);
    }

    public function view(Pengguna $pengguna, JadwalPelajaran $jadwal): bool
    {
        if ($pengguna->isAdmin()) {
            return true;
        }

        return $pengguna->guru?->id_guru === $jadwal->id_guru;
    }

    public function manage(Pengguna $pengguna): bool
    {
        return $pengguna->isAdmin();
    }
}
