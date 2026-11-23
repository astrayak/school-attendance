<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Http\Requests\SimpanAbsensiRequest;
use App\Models\CatatanAbsensi;
use App\Models\SesiAbsensi;
use App\Services\LayananAbsensi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AbsensiController extends Controller
{
    public function __construct(private readonly LayananAbsensi $absensi)
    {
    }

    public function show(Request $request, SesiAbsensi $sesi): View
    {
        $this->authorize('view', $sesi);

        $sesi->load([
            'jadwal.kelas',
            'jadwal.mataPelajaran',
            'jadwal.versiJadwal.semester',
            'guruPenanggungJawab',
            'guruPelaksana',
        ]);

        $siswa = $this->absensi->siswaPadaSesi($sesi);

        $catatan = CatatanAbsensi::query()
            ->where('id_sesi_absensi', $sesi->id_sesi_absensi)
            ->get()
            ->keyBy('id_siswa');

        $bolehMengisi = $this->absensi->bolehMengisi($sesi, $request->user());
        $jendelaTerbuka = $this->absensi->jendelaTerbuka($sesi);

        return view('guru.absensi.show', compact('sesi', 'siswa', 'catatan', 'bolehMengisi', 'jendelaTerbuka'));
    }

    public function simpan(SimpanAbsensiRequest $request, SesiAbsensi $sesi): RedirectResponse
    {
        $this->authorize('view', $sesi);

        $data = $request->validated();

        $this->absensi->simpan(
            $sesi,
            $data['absensi'],
            SesiAbsensi::METODE_MANUAL,
            $request->user(),
        );

        return redirect()
            ->route('guru.sesi.show', $sesi)
            ->with('sukses', 'Absensi berhasil disimpan. Permintaan konfirmasi pembelajaran telah dikirim ke petugas siswa.');
    }
}
