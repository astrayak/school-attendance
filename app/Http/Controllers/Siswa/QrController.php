<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Siswa\Concerns\ProfilSiswa;
use App\Services\LayananQrCode;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QrController extends Controller
{
    use ProfilSiswa;

    public function __invoke(Request $request, LayananQrCode $qr): View
    {
        $siswa = $this->profilSiswa($request);
        $kelas = $siswa->kelasSaatIni();

        $kode = $qr->pastikanAktif($siswa);
        $svg = $qr->gambarSvg($kode, 340);

        return view('siswa.qr', compact('siswa', 'kelas', 'kode', 'svg'));
    }
}
