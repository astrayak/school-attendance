<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Siswa\Concerns\ProfilSiswa;
use App\Models\CatatanAbsensi;
use App\Services\LayananPeriode;
use App\Support\Periode;
use App\Support\Status;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RiwayatController extends Controller
{
    use ProfilSiswa;

    public function __construct(private readonly LayananPeriode $periode)
    {
    }

    public function index(Request $request): View
    {
        $siswa = $this->profilSiswa($request);

        $semesterList = $this->periode->semua();

        $idSemester = $request->has('semester')
            ? ($request->integer('semester') ?: null)
            : $this->periode->idAktif();

        $semester = $idSemester
            ? $semesterList->firstWhere('id_semester', $idSemester)
            : null;

        if ($semester) {
            [$dari, $sampai] = $this->periode->rentang($semester);
        } else {
            $dari = Periode::tanggal($request->query('dari'), now()->startOfMonth());
            $sampai = Periode::tanggal($request->query('sampai'));
        }

        if ($dari->gt($sampai)) {
            [$dari, $sampai] = [$sampai, $dari];
        }

        if ($dari->diffInDays($sampai) > 366) {
            $dari = $sampai->copy()->subDays(366);
        }

        $status = $request->query('status');
        $status = array_key_exists((string) $status, Status::KEHADIRAN) ? $status : null;

        $dasar = CatatanAbsensi::query()
            ->where('id_siswa', $siswa->id_siswa)
            ->whereHas('sesiAbsensi', function ($q) use ($dari, $sampai, $semester) {
                $q->whereBetween('tanggal_pelajaran', [$dari->toDateString(), $sampai->toDateString()]);

                if ($semester) {
                    $q->whereHas('jadwal.versiJadwal', fn ($qq) => $qq->where('id_semester', $semester->id_semester));
                }
            })
            ->when($status, fn ($q) => $q->where('status_kehadiran', $status));

        $ringkasan = [
            'hadir' => (clone $dasar)->where('status_kehadiran', CatatanAbsensi::HADIR)->count(),
            'izin' => (clone $dasar)->where('status_kehadiran', CatatanAbsensi::IZIN)->count(),
            'sakit' => (clone $dasar)->where('status_kehadiran', CatatanAbsensi::SAKIT)->count(),
            'alpha' => (clone $dasar)->where('status_kehadiran', CatatanAbsensi::ALPHA)->count(),
            'total' => (clone $dasar)->count(),
        ];

        $catatan = $dasar
            ->with([
                'sesiAbsensi.jadwal.mataPelajaran',
                'sesiAbsensi.jadwal.kelas',
                'sesiAbsensi.guruPelaksana',
            ])
            ->orderByDesc('dibuat_pada')
            ->paginate(20)
            ->withQueryString();

        return view('siswa.riwayat.index', compact(
            'siswa',
            'catatan',
            'ringkasan',
            'dari',
            'sampai',
            'status',
            'semesterList',
            'semester',
            'idSemester',
        ));
    }

    public function show(Request $request, CatatanAbsensi $catatan): View
    {
        $siswa = $this->profilSiswa($request);

        abort_unless($catatan->id_siswa === $siswa->id_siswa, 403);

        $catatan->load([
            'sesiAbsensi.jadwal.mataPelajaran',
            'sesiAbsensi.jadwal.kelas',
            'sesiAbsensi.guruPenanggungJawab',
            'sesiAbsensi.guruPelaksana',
            'pencatat',
            'audit.pengguna',
        ]);

        return view('siswa.riwayat.show', compact('siswa', 'catatan'));
    }
}
