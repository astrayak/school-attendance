<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Siswa\Concerns\ProfilSiswa;
use App\Models\MataPelajaran;
use App\Models\Semester;
use App\Services\LayananPeriode;
use App\Services\LayananRekapAbsensi;
use App\Support\Periode;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RekapController extends Controller
{
    use ProfilSiswa;

    public function __construct(
        private readonly LayananRekapAbsensi $rekap,
        private readonly LayananPeriode $periode,
    ) {
    }

    public function mapel(Request $request): View
    {
        $siswa = $this->profilSiswa($request);

        $semesterList = $this->periode->semua();
        $idSemester = $this->semesterTerpilih($request, $semesterList);
        $semester = $idSemester ? $semesterList->firstWhere('id_semester', $idSemester) : null;

        [$dari, $sampai] = $this->rentang($request, $semester);

        $idMapel = $request->integer('mapel') ?: null;
        $mataPelajaran = MataPelajaran::query()->orderBy('nama_mata_pelajaran')->get();
        $mapelTerpilih = $idMapel ? $mataPelajaran->firstWhere('id_mata_pelajaran', $idMapel) : null;

        $rekap = $this->rekap->rekapMapel($siswa->id_siswa, $dari, $sampai, $idMapel, $idSemester);

        $total = [
            'pertemuan' => (int) $rekap->sum('total_pertemuan'),
            'hadir' => (int) $rekap->sum('hadir'),
            'izin' => (int) $rekap->sum('izin'),
            'sakit' => (int) $rekap->sum('sakit'),
            'alpha' => (int) $rekap->sum('alpha'),
            'belum' => (int) $rekap->sum('belum_diisi'),
        ];

        $persentase = $total['pertemuan'] > 0
            ? round(($total['hadir'] / $total['pertemuan']) * 100, 1)
            : 0.0;

        $kelas = $siswa->kelasSaatIni();

        return view('siswa.rekap.mapel', compact(
            'siswa',
            'kelas',
            'rekap',
            'mataPelajaran',
            'mapelTerpilih',
            'total',
            'persentase',
            'dari',
            'sampai',
            'semesterList',
            'semester',
            'idSemester',
        ));
    }

    public function harian(Request $request): View
    {
        $siswa = $this->profilSiswa($request);

        $semesterList = $this->periode->semua();
        $idSemester = $this->semesterTerpilih($request, $semesterList);
        $semester = $idSemester ? $semesterList->firstWhere('id_semester', $idSemester) : null;

        [$dari, $sampai] = $this->rentang($request, $semester);

        $rekap = $this->rekap->rekapHarian($siswa->id_siswa, $dari, $sampai, $idSemester);

        $total = [
            'hari' => $rekap->count(),
            'mapel' => (int) $rekap->sum('jumlah_mapel_dijadwalkan'),
            'hadir' => (int) $rekap->sum('jumlah_hadir'),
            'izin' => (int) $rekap->sum('jumlah_izin'),
            'sakit' => (int) $rekap->sum('jumlah_sakit'),
            'alpha' => (int) $rekap->sum('jumlah_alpha'),
            'belum' => (int) $rekap->sum('jumlah_belum_diisi'),
        ];

        $kelas = $siswa->kelasSaatIni();

        return view('siswa.rekap.harian', compact(
            'siswa',
            'kelas',
            'rekap',
            'total',
            'dari',
            'sampai',
            'semesterList',
            'semester',
            'idSemester',
        ));
    }

    protected function semesterTerpilih(Request $request, $semesterList): ?int
    {
        if ($request->has('semester')) {
            return $request->integer('semester') ?: null;
        }

        $idAktif = $this->periode->idAktif();

        if (! $idAktif || ! $semesterList->contains('id_semester', $idAktif)) {
            return null;
        }

        return $idAktif;
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function rentang(Request $request, ?Semester $semester): array
    {
        if ($semester) {
            return $this->periode->rentang($semester);
        }

        $dari = Periode::tanggal($request->query('dari'), now()->startOfMonth());
        $sampai = Periode::tanggal($request->query('sampai'));

        if ($dari->gt($sampai)) {
            [$dari, $sampai] = [$sampai, $dari];
        }

        if ($dari->diffInDays($sampai) > 366) {
            $dari = $sampai->copy()->subDays(366);
        }

        return [$dari, $sampai];
    }
}