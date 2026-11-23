<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\LogAktivitas;
use App\Models\MataPelajaran;
use App\Models\RingkasanAbsensiHarian;
use App\Models\SesiAbsensi;
use App\Models\Siswa;
use App\Services\LayananSesi;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(LayananSesi $layananSesi): View
    {
        $sesiHariIni = $layananSesi->sesiHariIni();
        $ringkasan = $layananSesi->ringkasanHariIni();

        $perhatian = $sesiHariIni
            ->filter(fn (SesiAbsensi $sesi) => $sesi->waktu_disimpan === null
                || in_array($sesi->status_sesi, [
                    SesiAbsensi::STATUS_MENUNGGU_KONFIRMASI,
                    SesiAbsensi::STATUS_PERLU_PEMERIKSAAN,
                    SesiAbsensi::STATUS_BELUM_DIKONFIRMASI,
                ], true))
            ->take(8)
            ->values();

        $statistik = [
            'siswa_aktif' => Siswa::query()->where('status_siswa', 'aktif')->count(),
            'guru_aktif' => Guru::query()->where('status_guru', 'aktif')->count(),
            'kelas_aktif' => Kelas::query()->where('status_kelas', 'aktif')->count(),
            'mapel_aktif' => MataPelajaran::query()->where('status_aktif', true)->count(),
        ];

        $logTerbaru = LogAktivitas::query()
            ->with('pengguna')
            ->orderByDesc('waktu_aktivitas')
            ->limit(8)
            ->get();

        $grafik = $this->grafikMingguan();

        return view('admin.dashboard', compact(
            'sesiHariIni',
            'ringkasan',
            'perhatian',
            'statistik',
            'logTerbaru',
            'grafik',
        ));
    }

    /**
     * @return array<int, array{tanggal: string, label: string, hadir: int, izin: int, sakit: int, alpha: int, belum: int}>
     */
    protected function grafikMingguan(): array
    {
        $mulai = now()->subDays(6)->startOfDay();
        $akhir = now()->startOfDay();

        $data = RingkasanAbsensiHarian::query()
            ->whereBetween('tanggal', [$mulai->toDateString(), $akhir->toDateString()])
            ->get()
            ->groupBy(fn (RingkasanAbsensiHarian $baris) => $baris->tanggal->toDateString());

        $hasil = [];

        for ($tanggal = $mulai->copy(); $tanggal->lte($akhir); $tanggal->addDay()) {
            $kunci = $tanggal->toDateString();
            $baris = $data->get($kunci, collect());

            $hasil[] = [
                'tanggal' => $kunci,
                'label' => Carbon::parse($kunci)->locale('id')->translatedFormat('D'),
                'hadir' => (int) $baris->sum('jumlah_hadir'),
                'izin' => (int) $baris->sum('jumlah_izin'),
                'sakit' => (int) $baris->sum('jumlah_sakit'),
                'alpha' => (int) $baris->sum('jumlah_alpha'),
                'belum' => (int) $baris->sum('jumlah_belum_diisi'),
            ];
        }

        return $hasil;
    }
}
