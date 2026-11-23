<?php

namespace App\Http\Controllers\Guru\Concerns;

use App\Models\Guru;
use Illuminate\Http\Request;

trait ProfilGuru
{
    protected function profilGuru(Request $request): Guru
    {
        $guru = $request->user()->guru;

        abort_unless($guru instanceof Guru, 403, 'Akun ini belum memiliki profil guru.');

        return $guru;
    }
}
