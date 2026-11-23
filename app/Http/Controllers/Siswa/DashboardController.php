<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Siswa\Concerns\ProfilSiswa;
use App\Models\CatatanAbsensi;
use App\Models\SesiAbsensi;
use App\Services\LayananKonfirmasi;
use App\Services\LayananPeriode;
use App\Services\LayananRekapAbsensi;
use App\Services\LayananSesi;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use ProfilSiswa;

    public function __invoke(
        Request $request,
        LayananSesi $layananSesi,
        LayananKonfirmasi $layananKonfirmasi,
        LayananRekapAbsensi $layananRekap,
        LayananPeriode $periode,
    ): View {
        $siswa = $this->profilSiswa($request);
        $kelas = $siswa->kelasSaatIni();
        $semesterAktif = $periode->aktif();
        [$awalSemester, $akhirSemester] = $periode->rentang($semesterAktif);

        $sesiHariIni = $layananSesi->sesiHariIni()
            ->when($kelas, fn ($daftar) => $daftar->filter(
                fn (SesiAbsensi $sesi) => $sesi->jadwal?->id_kelas === $kelas->id_kelas
            ))
            ->when(! $kelas, fn ($daftar) => $daftar->filter(fn () => false))
            ->values();

        $catatanHariIni = CatatanAbsensi::query()
            ->where('id_siswa', $siswa->id_siswa)
            ->whereIn('id_sesi_absensi', $sesiHariIni->pluck('id_sesi_absensi'))
            ->get()
            ->keyBy('id_sesi_absensi');

        $rekapHariIni = $layananRekap
            ->rekapHarian($siswa->id_siswa, now()->startOfDay(), now()->startOfDay())
            ->first();

        $rekapSemester = $layananRekap->rekapMapel(
            $siswa->id_siswa,
            $awalSemester,
            $akhirSemester,
            null,
            $semesterAktif?->id_semester,
        );

        $riwayatTerbaru = CatatanAbsensi::query()
            ->with(['sesiAbsensi.jadwal.mataPelajaran', 'sesiAbsensi.jadwal.kelas'])
            ->where('id_siswa', $siswa->id_siswa)
            ->when($semesterAktif, fn ($q) => $q->whereHas('sesiAbsensi.jadwal.versiJadwal', fn ($qq) => $qq->where('id_semester', $semesterAktif->id_semester)))
            ->orderByDesc('dibuat_pada')
            ->limit(5)
            ->get();

        $jumlahMenunggu = $layananKonfirmasi->jumlahMenunggu($siswa);
        $permintaanMenunggu = $layananKonfirmasi->daftarUntukSiswa($siswa)
            ->where('status_permintaan', 'menunggu')
            ->take(3)
            ->values();

        $statistik = [
            'hadir' => (int) $rekapSemester->sum('hadir'),
            'izin' => (int) $rekapSemester->sum('izin'),
            'sakit' => (int) $rekapSemester->sum('sakit'),
            'alpha' => (int) $rekapSemester->sum('alpha'),
            'total' => (int) $rekapSemester->sum('total_pertemuan'),
            'persentase' => $rekapSemester->sum('total_pertemuan') > 0
                ? round(((int) $rekapSemester->sum('hadir') / (int) $rekapSemester->sum('total_pertemuan')) * 100, 1)
                : 0.0,
        ];

        $labelPeriode = $periode->label($semesterAktif);

        return view('siswa.dashboard', compact(
            'siswa',
            'kelas',
            'semesterAktif',
            'labelPeriode',
            'sesiHariIni',
            'catatanHariIni',
            'rekapHariIni',
            'rekapSemester',
            'riwayatTerbaru',
            'jumlahMenunggu',
            'permintaanMenunggu',
            'statistik',
        ));
    }
}
