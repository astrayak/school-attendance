<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\KoreksiAbsensiRequest;
use App\Models\AuditAbsensi;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\LogAktivitas;
use App\Models\SesiAbsensi;
use App\Services\LayananAbsensi;
use App\Services\LayananSesi;
use App\Support\Periode;
use App\Support\Status;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SesiAbsensiController extends Controller
{
    public function __construct(
        private readonly LayananSesi $layananSesi,
        private readonly LayananAbsensi $absensi,
    ) {
    }

    public function index(Request $request): View
    {
        $tanggal = Periode::tanggal($request->query('tanggal'));
        $this->layananSesi->sinkronkanTanggal($tanggal);

        $idKelas = $request->integer('kelas') ?: null;
        $idGuru = $request->integer('guru') ?: null;
        $status = $request->query('status');
        $pencarian = trim((string) $request->query('q'));

        $sesi = SesiAbsensi::query()
            ->with(['jadwal.kelas', 'jadwal.mataPelajaran', 'guruPenanggungJawab', 'guruPelaksana'])
            ->withCount(['catatanAbsensi', 'permintaanKonfirmasi'])
            ->whereDate('tanggal_pelajaran', $tanggal->toDateString())
            ->when($idKelas, fn ($q) => $q->whereHas('jadwal', fn ($j) => $j->where('id_kelas', $idKelas)))
            ->when($idGuru, fn ($q) => $q->where(function ($qq) use ($idGuru) {
                $qq->where('id_guru_penanggung_jawab', $idGuru)
                    ->orWhere('id_guru_pelaksana', $idGuru);
            }))
            ->when(array_key_exists((string) $status, Status::SESI), fn ($q) => $q->where('status_sesi', $status))
            ->when($pencarian !== '', function ($q) use ($pencarian) {
                $q->where(function ($qq) use ($pencarian) {
                    $qq->whereHas('jadwal.mataPelajaran', fn ($m) => $m->where('nama_mata_pelajaran', 'like', "%{$pencarian}%"))
                        ->orWhereHas('jadwal.kelas', fn ($k) => $k->where('nama_kelas', 'like', "%{$pencarian}%"))
                        ->orWhereHas('guruPenanggungJawab', fn ($g) => $g->where('nama_lengkap', 'like', "%{$pencarian}%"));
                });
            })
            ->orderBy('waktu_mulai')
            ->paginate(20)
            ->withQueryString();

        $dasar = SesiAbsensi::query()
            ->whereDate('tanggal_pelajaran', $tanggal->toDateString())
            ->where('status_sesi', '!=', SesiAbsensi::STATUS_DIBATALKAN);

        $statistik = [
            'total' => (clone $dasar)->count(),
            'belum' => (clone $dasar)->whereNull('waktu_disimpan')->count(),
            'menunggu_konfirmasi' => (clone $dasar)->where('status_sesi', SesiAbsensi::STATUS_MENUNGGU_KONFIRMASI)->count(),
            'terkonfirmasi' => (clone $dasar)->where('status_sesi', SesiAbsensi::STATUS_TERKONFIRMASI)->count(),
            'perlu_pemeriksaan' => (clone $dasar)->where('status_sesi', SesiAbsensi::STATUS_PERLU_PEMERIKSAAN)->count(),
            'belum_dikonfirmasi' => (clone $dasar)->where('status_sesi', SesiAbsensi::STATUS_BELUM_DIKONFIRMASI)->count(),
        ];

        $kelas = Kelas::query()->orderBy('nama_kelas')->get();
        $guru = Guru::query()->orderBy('nama_lengkap')->get();

        return view('admin.sesi.index', compact(
            'sesi',
            'statistik',
            'kelas',
            'guru',
            'tanggal',
            'idKelas',
            'idGuru',
            'status',
            'pencarian',
        ));
    }

    public function show(Request $request, SesiAbsensi $sesi): View
    {
        $sesi->load([
            'jadwal.kelas',
            'jadwal.mataPelajaran',
            'jadwal.versiJadwal.semester',
            'guruPenanggungJawab',
            'guruPelaksana',
            'catatanAbsensi.siswa',
            'catatanAbsensi.pencatat',
            'permintaanKonfirmasi.siswaPenerima',
            'permintaanKonfirmasi.petugasKonfirmasi',
        ]);

        $siswa = $this->absensi->siswaPadaSesi($sesi);
        $catatan = $sesi->catatanAbsensi->keyBy('id_siswa');

        $audit = AuditAbsensi::query()
            ->with('pengguna')
            ->whereIn('id_catatan_absensi', $sesi->catatanAbsensi->pluck('id_catatan_absensi'))
            ->orderByDesc('waktu_perubahan')
            ->get()
            ->groupBy('id_catatan_absensi');

        $riwayatSesi = LogAktivitas::query()
            ->with('pengguna')
            ->where('jenis_data', 'sesi_absensi')
            ->where('id_data_terkait', (string) $sesi->id_sesi_absensi)
            ->orderByDesc('waktu_aktivitas')
            ->limit(20)
            ->get();

        $bolehKoreksi = $request->user()->can('koreksi', $sesi);
        $bolehBatal = $request->user()->can('batalkan', $sesi);

        return view('admin.sesi.show', compact(
            'sesi',
            'siswa',
            'catatan',
            'audit',
            'riwayatSesi',
            'bolehKoreksi',
            'bolehBatal',
        ));
    }

    public function koreksi(KoreksiAbsensiRequest $request, SesiAbsensi $sesi): RedirectResponse
    {
        $this->authorize('koreksi', $sesi);

        $data = $request->validated();

        $this->absensi->simpan(
            $sesi,
            $data['absensi'],
            $sesi->metode_absensi ?? SesiAbsensi::METODE_MANUAL,
            $request->user(),
            $data['alasan'],
            true,
        );

        return redirect()
            ->route('admin.sesi.show', $sesi)
            ->with('sukses', 'Koreksi absensi berhasil disimpan dan audit perubahan tercatat.');
    }

    public function batalkan(Request $request, SesiAbsensi $sesi): RedirectResponse
    {
        $this->authorize('batalkan', $sesi);

        $data = $request->validate([
            'alasan' => ['required', 'string', 'min:5', 'max:255'],
        ], [], ['alasan' => 'alasan pembatalan']);

        $this->absensi->batalkan($sesi, $request->user(), $data['alasan']);

        return redirect()
            ->route('admin.sesi.show', $sesi)
            ->with('sukses', 'Sesi absensi berhasil dibatalkan.');
    }
}
