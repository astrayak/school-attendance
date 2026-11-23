<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Guru\Concerns\ProfilGuru;
use App\Models\JadwalGuruPiket;
use App\Models\SesiAbsensi;
use App\Services\LayananGuruPiket;
use App\Services\LayananJadwal;
use App\Services\LayananPeriode;
use App\Services\LayananSesi;
use App\Support\Status;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use ProfilGuru;

    public function __invoke(
        Request $request,
        LayananSesi $layananSesi,
        LayananGuruPiket $layananPiket,
        LayananJadwal $layananJadwal,
        LayananPeriode $periode,
    ): View {
        $guru = $this->profilGuru($request);
        $pengguna = $request->user();

        $sesiHariIni = $layananSesi->sesiHariIni();

        $sesiSaya = $sesiHariIni
            ->filter(fn (SesiAbsensi $sesi) => $sesi->id_guru_penanggung_jawab === $guru->id_guru
                || $sesi->id_guru_pelaksana === $guru->id_guru)
            ->values();

        $piketAktif = $layananPiket->penugasanAktif($pengguna, now());
        $sesiPiket = $piketAktif ? $layananPiket->sesiBelumDitangani($pengguna, now()) : collect();

        $statistik = [
            'sesi_saya' => $sesiSaya->count(),
            'belum_diisi' => $sesiSaya->whereNull('waktu_disimpan')->count(),
            'menunggu_konfirmasi' => $sesiSaya->where('status_sesi', SesiAbsensi::STATUS_MENUNGGU_KONFIRMASI)->count(),
            'perlu_pemeriksaan' => $sesiSaya->where('status_sesi', SesiAbsensi::STATUS_PERLU_PEMERIKSAAN)->count(),
            'piket' => $sesiPiket->count(),
        ];

        $versi = $layananJadwal->versiAktifSekarang();
        $semesterAktif = $periode->aktif();

        $jadwalMingguIni = $versi
            ? $layananJadwal->jadwalGuru($versi, $guru->id_guru, now())->groupBy('hari')
            : collect();

        $jadwalPiket = JadwalGuruPiket::query()
            ->with('semester')
            ->where('id_guru', $guru->id_guru)
            ->when(
                $semesterAktif,
                fn ($q) => $q->where('id_semester', $semesterAktif->id_semester),
                fn ($q) => $q->whereRaw('1 = 0'),
            )
            ->where('status_aktif', true)
            ->orderByRaw("FIELD(hari, 'senin','selasa','rabu','kamis','jumat','sabtu','minggu')")
            ->get();

        $notifikasi = $pengguna->notifikasi()
            ->where('sudah_dibaca', false)
            ->orderByDesc('dibuat_pada')
            ->limit(5)
            ->get();

        $urutanHari = array_keys(Status::HARI);
        $labelPeriode = $periode->label($semesterAktif);

        return view('guru.dashboard', compact(
            'guru',
            'sesiSaya',
            'statistik',
            'piketAktif',
            'sesiPiket',
            'versi',
            'semesterAktif',
            'labelPeriode',
            'jadwalMingguIni',
            'jadwalPiket',
            'notifikasi',
            'urutanHari',
        ));
    }
}
