<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Guru\Concerns\ProfilGuru;
use App\Models\SesiAbsensi;
use App\Services\LayananAbsensi;
use App\Services\LayananGuruPiket;
use App\Services\LayananSesi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PiketController extends Controller
{
    use ProfilGuru;

    public function __construct(
        private readonly LayananGuruPiket $piket,
        private readonly LayananSesi $layananSesi,
        private readonly LayananAbsensi $absensi,
    ) {
    }

    public function index(Request $request): View
    {
        $guru = $this->profilGuru($request);
        $pengguna = $request->user();
        $tanggal = now();

        $piketAktif = $this->piket->penugasanAktif($pengguna, $tanggal);
        $sesiBelumDitangani = $piketAktif ? $this->piket->sesiBelumDitangani($pengguna, $tanggal) : collect();

        $sesiDiambil = SesiAbsensi::query()
            ->with(['jadwal.kelas', 'jadwal.mataPelajaran', 'guruPelaksana'])
            ->whereDate('tanggal_pelajaran', $tanggal->toDateString())
            ->where('id_guru_pelaksana', $guru->id_guru)
            ->orderBy('waktu_mulai')
            ->get();

        $layananHariIni = $this->layananSesi->sesiHariIni();

        $statistik = [
            'total_hari_ini' => $layananHariIni->count(),
            'belum_diisi' => $layananHariIni->whereNull('waktu_disimpan')->count(),
            'diambil_alih' => $sesiDiambil->count(),
            'perlu_tindakan' => $sesiBelumDitangani->count(),
        ];

        return view('guru.piket', compact(
            'guru',
            'piketAktif',
            'sesiBelumDitangani',
            'sesiDiambil',
            'statistik',
        ));
    }

    public function ambilAlih(Request $request, SesiAbsensi $sesi): RedirectResponse
    {
        $data = $request->validate([
            'alasan' => ['required', 'string', 'min:5', 'max:255'],
            'tujuan' => ['nullable', 'in:absensi,scanner'],
        ], [], ['alasan' => 'alasan pengambilalihan']);

        $this->piket->ambilAlih($sesi, $request->user(), $data['alasan']);

        $tujuan = ($data['tujuan'] ?? 'absensi') === 'scanner' ? 'guru.scanner.show' : 'guru.absensi.show';

        return redirect()
            ->route($tujuan, $sesi)
            ->with('sukses', 'Sesi berhasil diambil alih. Anda sekarang dapat mengisi absensi sesi ini.');
    }
}
