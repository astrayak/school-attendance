<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Guru\Concerns\ProfilGuru;
use App\Models\AuditAbsensi;
use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\SesiAbsensi;
use App\Services\LayananAbsensi;
use App\Services\LayananGuruPiket;
use App\Services\LayananJadwal;
use App\Services\LayananSesi;
use App\Support\Periode;
use App\Support\Status;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SesiController extends Controller
{
    use ProfilGuru;

    public function __construct(
        private readonly LayananSesi $layananSesi,
        private readonly LayananAbsensi $absensi,
        private readonly LayananGuruPiket $piket,
        private readonly LayananJadwal $jadwal,
    ) {
    }

    public function index(Request $request): View
    {
        $guru = $this->profilGuru($request);
        $pengguna = $request->user();

        $tanggal = Periode::tanggal($request->query('tanggal'));
        $this->layananSesi->sinkronkanTanggal($tanggal);

        $idKelas = $request->integer('kelas') ?: null;
        $status = $request->query('status');
        $piketAktif = $this->piket->adalahPiket($pengguna, $tanggal);
        $versi = $this->jadwal->versiAktifSekarang();

        $sesi = SesiAbsensi::query()
            ->with(['jadwal.kelas', 'jadwal.mataPelajaran', 'guruPenanggungJawab', 'guruPelaksana'])
            ->withCount('catatanAbsensi')
            ->whereDate('tanggal_pelajaran', $tanggal->toDateString())
            ->where(function ($q) use ($guru, $piketAktif) {
                $q->where('id_guru_penanggung_jawab', $guru->id_guru)
                    ->orWhere('id_guru_pelaksana', $guru->id_guru);

                if ($piketAktif) {
                    $q->orWhereNotNull('id_sesi_absensi');
                }
            })
            ->when($idKelas, fn ($q) => $q->whereHas('jadwal', fn ($j) => $j->where('id_kelas', $idKelas)))
            ->when(array_key_exists((string) $status, Status::SESI), fn ($q) => $q->where('status_sesi', $status))
            ->orderBy('waktu_mulai')
            ->paginate(20)
            ->withQueryString();

        $kelas = Kelas::query()
            ->whereIn('id_kelas', JadwalPelajaran::query()
                ->where('id_guru', $guru->id_guru)
                ->when($versi, fn ($q) => $q->where('id_versi_jadwal', $versi->id_versi_jadwal))
                ->select('id_kelas'))
            ->orderBy('nama_kelas')
            ->get();

        return view('guru.sesi.index', compact(
            'guru',
            'sesi',
            'kelas',
            'tanggal',
            'idKelas',
            'status',
            'piketAktif',
        ));
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
            'catatanAbsensi.siswa',
            'catatanAbsensi.pencatat',
            'permintaanKonfirmasi.siswaPenerima',
        ]);

        $siswa = $this->absensi->siswaPadaSesi($sesi);
        $catatan = $sesi->catatanAbsensi->keyBy('id_siswa');

        $audit = AuditAbsensi::query()
            ->with('pengguna')
            ->whereIn('id_catatan_absensi', $sesi->catatanAbsensi->pluck('id_catatan_absensi'))
            ->orderByDesc('waktu_perubahan')
            ->get()
            ->groupBy('id_catatan_absensi');

        $bolehMengisi = $this->absensi->bolehMengisi($sesi, $request->user());

        return view('guru.sesi.show', compact('sesi', 'siswa', 'catatan', 'audit', 'bolehMengisi'));
    }
}
