<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Guru\Concerns\ProfilGuru;
use App\Models\JadwalGuruPiket;
use App\Services\LayananJadwal;
use App\Services\LayananPeriode;
use App\Support\Status;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JadwalController extends Controller
{
    use ProfilGuru;

    public function __invoke(
        Request $request,
        LayananJadwal $layananJadwal,
        LayananPeriode $periode,
    ): View {
        $guru = $this->profilGuru($request);

        $semesterList = $periode->semua();
        $idSemester = $request->has('semester')
            ? ($request->integer('semester') ?: null)
            : $periode->idAktif();
        $semester = $idSemester ? $semesterList->firstWhere('id_semester', $idSemester) : null;

        $tanggal = $semester && $periode->mencakup($semester, now())
            ? now()
            : ($semester?->tanggal_mulai ?? now());

        $versi = $semester ? $layananJadwal->versiUntukSemester($semester, $tanggal) : null;

        $jadwal = $versi
            ? $layananJadwal->jadwalGuru($versi, $guru->id_guru, $tanggal)->groupBy('hari')
            : collect();

        $piket = JadwalGuruPiket::query()
            ->with('semester')
            ->where('id_guru', $guru->id_guru)
            ->when(
                $semester,
                fn ($q) => $q->where('id_semester', $semester->id_semester),
                fn ($q) => $q->whereRaw('1 = 0'),
            )
            ->where('status_aktif', true)
            ->orderByRaw("FIELD(hari, 'senin','selasa','rabu','kamis','jumat','sabtu','minggu')")
            ->get();

        $urutanHari = array_keys(Status::HARI);
        $labelPeriode = $periode->label($semester);

        return view('guru.jadwal', compact(
            'guru',
            'versi',
            'semester',
            'semesterList',
            'idSemester',
            'tanggal',
            'jadwal',
            'piket',
            'urutanHari',
            'labelPeriode',
        ));
    }
}