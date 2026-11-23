<?php

namespace App\Http\Controllers\Siswa\Concerns;

use App\Models\Siswa;
use Illuminate\Http\Request;

trait ProfilSiswa
{
    protected function profilSiswa(Request $request): Siswa
    {
        $siswa = $request->user()->siswa;

        abort_unless($siswa instanceof Siswa, 403, 'Akun ini belum memiliki profil siswa.');

        return $siswa;
    }
}
