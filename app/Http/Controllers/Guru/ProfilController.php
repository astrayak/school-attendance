<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Guru\Concerns\ProfilGuru;
use App\Services\LayananFotoProfil;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfilController extends Controller
{
    use ProfilGuru;

    public function __construct(private readonly LayananFotoProfil $foto)
    {
    }

    public function __invoke(Request $request): View
    {
        $guru = $this->profilGuru($request);

        $guru->load([
            'pengguna',
            'kelasWali',
            'jadwalPiket.semester',
        ]);

        $jumlahPenugasan = $guru->penugasan()->count();
        $jumlahJadwal = $guru->jadwal()->count();

        return view('guru.profil', compact('guru', 'jumlahPenugasan', 'jumlahJadwal'));
    }

    public function perbaruiFoto(Request $request): RedirectResponse
    {
        $request->validate(['foto' => $this->foto->aturan()]);

        $guru = $this->profilGuru($request);

        $this->foto->simpan(
            $request->user(),
            $request->file('foto'),
            'Profil Guru',
            'guru',
            $guru->id_guru,
        );

        return back()->with('sukses', 'Foto profil berhasil diperbarui.');
    }
}