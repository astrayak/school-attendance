<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\SesiAbsensi;
use App\Services\LayananLogAktivitas;
use App\Services\LayananPeriode;
use App\Services\LayananRekapAbsensi;
use App\Support\Periode;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanController extends Controller
{
    public function __construct(
        private readonly LayananRekapAbsensi $rekap,
        private readonly LayananLogAktivitas $log,
        private readonly LayananPeriode $periode,
    ) {
    }

    public function index(Request $request): View
    {
        $konteks = $this->konteks($request);

        return view('admin.laporan.index', $konteks);
    }

    public function ekspor(Request $request): StreamedResponse|RedirectResponse
    {
        $konteks = $this->konteks($request);

        if (! $konteks['kelasTerpilih']) {
            return redirect()
                ->route('admin.laporan.index')
                ->with('error', 'Pilih kelas terlebih dahulu sebelum mengekspor laporan.');
        }

        /** @var Kelas $kelas */
        $kelas = $konteks['kelasTerpilih'];
        /** @var Collection<int, array<string, mixed>> $laporan */
        $laporan = $konteks['laporan'];
        $dari = $konteks['dari'];
        $sampai = $konteks['sampai'];

        $this->log->catat(
            $request->user(),
            'ekspor_laporan',
            'Laporan',
            "Mengekspor laporan absensi kelas {$kelas->nama_kelas} periode {$dari->format('d/m/Y')} - {$sampai->format('d/m/Y')}.",
            'kelas',
            $kelas->id_kelas
        );

        $namaFile = 'laporan-absensi-'.str_replace(' ', '-', strtolower($kelas->nama_kelas))
            .'-'.$dari->format('Ymd').'-'.$sampai->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($laporan) {
            $keluaran = fopen('php://output', 'w');
            fwrite($keluaran, "\xEF\xBB\xBF");

            fputcsv($keluaran, [
                'NIS',
                'Nama Siswa',
                'Total Pertemuan',
                'Hadir',
                'Izin',
                'Sakit',
                'Alpha',
                'Belum Diisi',
                'Persentase Hadir (%)',
            ]);

            foreach ($laporan as $baris) {
                fputcsv($keluaran, [
                    $baris['nis'],
                    $baris['nama_lengkap'],
                    $baris['total_pertemuan'],
                    $baris['hadir'],
                    $baris['izin'],
                    $baris['sakit'],
                    $baris['alpha'],
                    $baris['belum_diisi'],
                    $baris['persentase_hadir'],
                ]);
            }

            fclose($keluaran);
        }, $namaFile, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function konteks(Request $request): array
    {
        $daftarKelas = Kelas::query()
            ->with('tahunAjaran')
            ->orderByDesc('id_tahun_ajaran')
            ->orderBy('nama_kelas')
            ->get();

        $idKelas = $request->integer('kelas') ?: null;
        $kelasTerpilih = $idKelas
            ? $daftarKelas->firstWhere('id_kelas', $idKelas)
            : $daftarKelas->firstWhere('status_kelas', 'aktif') ?? $daftarKelas->first();

        $semesterAktif = $this->periode->aktif();

        $bawaanDari = $semesterAktif && $semesterAktif->tanggal_mulai->lte(now())
            ? $semesterAktif->tanggal_mulai->copy()
            : now()->startOfMonth();

        $dari = Periode::tanggal($request->query('dari'), $bawaanDari);
        $sampai = Periode::tanggal($request->query('sampai'));

        if ($dari->gt($sampai)) {
            [$dari, $sampai] = [$sampai, $dari];
        }

        if ($dari->diffInDays($sampai) > 366) {
            $dari = $sampai->copy()->subDays(366);
        }

        $idMapel = $request->integer('mapel') ?: null;
        $mataPelajaran = MataPelajaran::query()->orderBy('nama_mata_pelajaran')->get();
        $mapelTerpilih = $idMapel ? $mataPelajaran->firstWhere('id_mata_pelajaran', $idMapel) : null;

        $laporan = $kelasTerpilih
            ? $this->rekap->ringkasanKelas($kelasTerpilih->id_kelas, $dari, $sampai, $idMapel)
            : collect();

        $jumlahSesi = $kelasTerpilih
            ? SesiAbsensi::query()
                ->whereBetween('tanggal_pelajaran', [$dari->toDateString(), $sampai->toDateString()])
                ->where('status_sesi', '!=', SesiAbsensi::STATUS_DIBATALKAN)
                ->where('waktu_selesai', '<=', now())
                ->whereHas('jadwal', function ($q) use ($kelasTerpilih, $idMapel) {
                    $q->where('id_kelas', $kelasTerpilih->id_kelas);

                    if ($idMapel) {
                        $q->where('id_mata_pelajaran', $idMapel);
                    }
                })
                ->count()
            : 0;

        $total = [
            'siswa' => $laporan->count(),
            'hadir' => (int) $laporan->sum('hadir'),
            'izin' => (int) $laporan->sum('izin'),
            'sakit' => (int) $laporan->sum('sakit'),
            'alpha' => (int) $laporan->sum('alpha'),
            'belum_diisi' => (int) $laporan->sum('belum_diisi'),
        ];

        $persentaseKelas = $laporan->isNotEmpty()
            ? round($laporan->avg('persentase_hadir'), 1)
            : 0.0;

        return [
            'daftarKelas' => $daftarKelas,
            'kelasTerpilih' => $kelasTerpilih,
            'mataPelajaran' => $mataPelajaran,
            'mapelTerpilih' => $mapelTerpilih,
            'idMapel' => $idMapel,
            'laporan' => $laporan,
            'dari' => $dari,
            'sampai' => $sampai,
            'jumlahSesi' => $jumlahSesi,
            'total' => $total,
            'persentaseKelas' => $persentaseKelas,
            'semesterAktif' => $semesterAktif,
        ];
    }
}
