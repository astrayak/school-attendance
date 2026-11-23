<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Services\LayananPeriode;
use App\Services\LayananRekapAbsensi;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RekapSiswaController extends Controller
{
    public function __construct(
        private readonly LayananRekapAbsensi $rekap,
        private readonly LayananPeriode $periode,
    ) {
    }

    public function index(Request $request): View
    {
        $pencarian = trim((string) $request->query('q'));
        $idKelas = $request->integer('kelas') ?: null;

        $periode = (string) $request->query('periode', 'semester');
        $periode = in_array($periode, ['semester', 'bulan', '7hari'], true) ? $periode : 'semester';

        $status = (string) $request->query('status', 'semua');
        $status = in_array($status, ['aktif', 'lulus', 'pindah', 'nonaktif', 'semua'], true) ? $status : 'semua';

        [$dari, $sampai] = $this->rentang($periode);
        $semesterAktif = $this->periode->aktif();

        $dasar = Siswa::query()
            ->with(['pengguna', 'riwayatKelasAktif.kelas'])
            ->when($pencarian !== '', function ($q) use ($pencarian) {
                $q->where(function ($qq) use ($pencarian) {
                    $qq->where('nama_lengkap', 'like', "%{$pencarian}%")
                        ->orWhere('nis', 'like', "%{$pencarian}%")
                        ->orWhere('nisn', 'like', "%{$pencarian}%")
                        ->orWhereHas('pengguna', fn ($p) => $p->where('email', 'like', "%{$pencarian}%"));
                });
            })
            ->when($idKelas, fn ($q) => $q->whereHas('riwayatKelasAktif', fn ($r) => $r->where('id_kelas', $idKelas)))
            ->when($status !== 'semua', fn ($q) => $q->where('status_siswa', $status));

        $siswa = (clone $dasar)
            ->orderBy('nama_lengkap')
            ->paginate(15)
            ->withQueryString();

        $idsSiswa = (clone $dasar)->pluck('id_siswa')->all();
        $rekap = $this->rekap->rekapBanyakSiswa(
            $idsSiswa,
            $dari,
            $sampai,
            $periode === 'semester' ? $semesterAktif?->id_semester : null,
        );

        $siswa->getCollection()->transform(function (Siswa $murid) use ($rekap) {
            $baris = $rekap->get($murid->id_siswa, [
                'total_pertemuan' => 0,
                'hadir' => 0,
                'izin' => 0,
                'sakit' => 0,
                'alpha' => 0,
                'belum_diisi' => 0,
            ]);

            $total = (int) $baris['total_pertemuan'];
            $hadir = (int) $baris['hadir'];

            $murid->setAttribute('rekap_total', $total);
            $murid->setAttribute('rekap_hadir', $hadir);
            $murid->setAttribute('rekap_izin', (int) $baris['izin']);
            $murid->setAttribute('rekap_sakit', (int) $baris['sakit']);
            $murid->setAttribute('rekap_alpha', (int) $baris['alpha']);
            $murid->setAttribute('rekap_belum', (int) $baris['belum_diisi']);
            $murid->setAttribute('rekap_persen', $total > 0 ? round(($hadir / $total) * 100, 1) : 0.0);

            return $murid;
        });

        $totalPertemuan = (int) $rekap->sum(fn ($baris) => $baris['total_pertemuan']);
        $totalHadir = (int) $rekap->sum(fn ($baris) => $baris['hadir']);
        $rataPersen = $totalPertemuan > 0 ? round(($totalHadir / $totalPertemuan) * 100, 1) : 0.0;
        $perluPerhatian = $rekap
            ->filter(fn ($baris) => $baris['total_pertemuan'] > 0 && ($baris['hadir'] / $baris['total_pertemuan'] * 100) < 75)
            ->count();

        $kelas = Kelas::query()
            ->where('status_kelas', 'aktif')
            ->orderBy('nama_kelas')
            ->get();

        $periodeLabel = match ($periode) {
            'bulan' => 'Bulan berjalan',
            '7hari' => '7 hari terakhir',
            default => 'Periode berjalan',
        };

        if ($periode === 'semester' && $semesterAktif) {
            $periodeLabel .= ' · '.ucfirst($semesterAktif->nama_semester)
                .' '.$semesterAktif->tahunAjaran?->nama_tahun_ajaran;
        }

        return view('admin.rekap-siswa.index', compact(
            'siswa',
            'kelas',
            'pencarian',
            'idKelas',
            'status',
            'periode',
            'periodeLabel',
            'semesterAktif',
            'dari',
            'sampai',
            'totalPertemuan',
            'totalHadir',
            'rataPersen',
            'perluPerhatian',
        ));
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function rentang(string $periode): array
    {
        $sampai = now()->startOfDay();
        $mulaiSemester = $this->periode->aktif()?->tanggal_mulai?->copy()->startOfDay();

        $dari = match ($periode) {
            'bulan' => now()->startOfMonth(),
            '7hari' => now()->subDays(6)->startOfDay(),
            default => $mulaiSemester ?? now()->startOfMonth(),
        };

        if ($dari->gt($sampai)) {
            $dari = $sampai->copy()->startOfMonth();
        }

        return [$dari, $sampai];
    }
}