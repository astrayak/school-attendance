<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Siswa\Concerns\ProfilSiswa;
use App\Services\LayananFotoProfil;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfilController extends Controller
{
    use ProfilSiswa;

    public function __construct(private readonly LayananFotoProfil $foto)
    {
    }

    public function __invoke(Request $request): View
    {
        $siswa = $this->profilSiswa($request);
        $siswa->load([
            'pengguna',
            'riwayatKelas.kelas.tahunAjaran',
            'kodeQrAktif',
        ]);

        $kelas = $siswa->kelasSaatIni();

        return view('siswa.profil', compact('siswa', 'kelas'));
    }

    public function perbaruiFoto(Request $request): RedirectResponse
    {
        $request->validate(['foto' => $this->foto->aturan()]);

        $siswa = $this->profilSiswa($request);

        $this->foto->simpan(
            $request->user(),
            $request->file('foto'),
            'Profil Siswa',
            'siswa',
            $siswa->id_siswa,
        );

        return back()->with('sukses', 'Foto profil berhasil diperbarui.');
    }
}