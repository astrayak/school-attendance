<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\JadwalRequest;
use App\Models\Guru;
use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\PenugasanGuru;
use App\Models\Semester;
use App\Models\SesiAbsensi;
use App\Models\VersiJadwal;
use App\Services\LayananJadwal;
use App\Services\LayananLogAktivitas;
use App\Services\LayananPeriode;
use App\Support\Status;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class JadwalController extends Controller
{
    public function __construct(
        private readonly LayananJadwal $jadwal,
        private readonly LayananLogAktivitas $log,
        private readonly LayananPeriode $periode,
    ) {
    }

    public function index(Request $request): View
    {
        $semester = Semester::query()->with('tahunAjaran')->orderByDesc('tanggal_mulai')->get();
        $semesterAktif = $this->periode->aktif();
        $semesterDitandaiAktif = Semester::query()
            ->with('tahunAjaran')
            ->where('status_aktif', true)
            ->orderByDesc('tanggal_mulai')
            ->first();

        $idSemester = $request->integer('semester') ?: $semesterAktif?->id_semester;
        $semesterTerpilih = $semester->firstWhere('id_semester', $idSemester);

        $versi = VersiJadwal::query()
            ->with('semester')
            ->when($idSemester, fn ($q) => $q->where('id_semester', $idSemester))
            ->orderByDesc('tanggal_berlaku')
            ->get();

        $idVersi = $request->integer('versi') ?: null;
        $versiTerpilih = $idVersi ? $versi->firstWhere('id_versi_jadwal', $idVersi) : null;

        if (! $versiTerpilih) {
            $versiTerpilih = $versi->first(fn (VersiJadwal $v) => $v->status_versi === VersiJadwal::STATUS_AKTIF
                && (! $v->tanggal_berakhir || $v->tanggal_berakhir->gte(now()->startOfDay())))
                ?? $versi->first();
        }

        $idKelas = $request->integer('kelas') ?: null;
        $hari = $request->query('hari');
        $hari = array_key_exists((string) $hari, Status::HARI) ? $hari : null;

        $jadwal = collect();

        if ($versiTerpilih) {
            $jadwal = JadwalPelajaran::query()
                ->with(['kelas', 'mataPelajaran', 'guru'])
                ->where('id_versi_jadwal', $versiTerpilih->id_versi_jadwal)
                ->when($idKelas, fn ($q) => $q->where('id_kelas', $idKelas))
                ->when($hari, fn ($q) => $q->where('hari', $hari))
                ->orderBy('jam_mulai')
                ->get();
        }

        $urutanHari = array_keys(Status::HARI);
        $kelompok = $jadwal->groupBy('hari')->sortBy(
            fn ($item, $kunci) => array_search($kunci, $urutanHari, true)
        );

        $kelas = $semesterTerpilih
            ? Kelas::query()
                ->where('id_tahun_ajaran', $semesterTerpilih->id_tahun_ajaran)
                ->orderBy('nama_kelas')
                ->get()
            : collect();

        $jumlahSesi = $versiTerpilih
            ? SesiAbsensi::query()
                ->whereHas('jadwal', fn ($q) => $q->where('id_versi_jadwal', $versiTerpilih->id_versi_jadwal))
                ->count()
            : 0;

        $bolehUbah = $versiTerpilih
            ? $versiTerpilih->status_versi === VersiJadwal::STATUS_DRAF && $jumlahSesi === 0
            : false;

        return view('admin.jadwal.index', compact(
            'semester',
            'semesterAktif',
            'semesterDitandaiAktif',
            'semesterTerpilih',
            'versi',
            'versiTerpilih',
            'jadwal',
            'kelompok',
            'urutanHari',
            'kelas',
            'idSemester',
            'idKelas',
            'hari',
            'jumlahSesi',
            'bolehUbah',
        ));
    }

    public function storeVersi(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'id_semester' => ['required', 'integer', 'exists:semester,id_semester'],
            'nama_versi' => [
                'required', 'string', 'max:100',
                Rule::unique('versi_jadwal', 'nama_versi')
                    ->where(fn ($q) => $q->where('id_semester', $request->integer('id_semester'))),
            ],
            'tanggal_berlaku' => ['required', 'date'],
        ], [], [
            'id_semester' => 'semester',
            'nama_versi' => 'nama versi',
            'tanggal_berlaku' => 'tanggal berlaku',
        ]);

        $semester = Semester::findOrFail($data['id_semester']);
        $tanggal = Carbon::parse($data['tanggal_berlaku'])->startOfDay();

        if ($tanggal->lt($semester->tanggal_mulai) || $tanggal->gt($semester->tanggal_selesai)) {
            throw ValidationException::withMessages([
                'tanggal_berlaku' => 'Tanggal berlaku harus berada di dalam rentang semester.',
            ]);
        }

        $versi = VersiJadwal::create([
            'id_semester' => $semester->id_semester,
            'nama_versi' => $data['nama_versi'],
            'tanggal_berlaku' => $tanggal->toDateString(),
            'status_versi' => VersiJadwal::STATUS_DRAF,
            'dibuat_oleh' => $request->user()->id_pengguna,
        ]);

        $this->log->catat(
            $request->user(),
            'tambah_versi_jadwal',
            'Jadwal Pelajaran',
            "Membuat versi jadwal {$versi->nama_versi}.",
            'versi_jadwal',
            $versi->id_versi_jadwal
        );

        return redirect()
            ->route('admin.jadwal.index', ['semester' => $semester->id_semester, 'versi' => $versi->id_versi_jadwal])
            ->with('sukses', 'Versi jadwal berhasil dibuat. Silakan tambahkan jadwal pelajaran.');
    }

    public function aktifkanVersi(Request $request, VersiJadwal $versi): RedirectResponse
    {
        $this->jadwal->aktifkanVersi($versi, $request->user());

        $this->log->catat(
            $request->user(),
            'aktifkan_versi_jadwal',
            'Jadwal Pelajaran',
            "Mengaktifkan versi jadwal {$versi->nama_versi}.",
            'versi_jadwal',
            $versi->id_versi_jadwal
        );

        return redirect()
            ->route('admin.jadwal.index', ['semester' => $versi->id_semester, 'versi' => $versi->id_versi_jadwal])
            ->with('sukses', 'Versi jadwal berhasil diaktifkan. Versi sebelumnya diarsipkan sesuai tanggal berlaku.');
    }

    public function revisi(Request $request, VersiJadwal $versi): RedirectResponse
    {
        $data = $request->validate([
            'nama_versi' => [
                'required', 'string', 'max:100',
                Rule::unique('versi_jadwal', 'nama_versi')
                    ->where(fn ($q) => $q->where('id_semester', $versi->id_semester)),
            ],
            'tanggal_berlaku' => ['required', 'date'],
        ], [], [
            'nama_versi' => 'nama versi',
            'tanggal_berlaku' => 'tanggal berlaku',
        ]);

        $semester = $versi->semester;
        $tanggal = Carbon::parse($data['tanggal_berlaku'])->startOfDay();

        if ($semester && ($tanggal->lt($semester->tanggal_mulai) || $tanggal->gt($semester->tanggal_selesai))) {
            throw ValidationException::withMessages([
                'tanggal_berlaku' => 'Tanggal berlaku harus berada di dalam rentang semester.',
            ]);
        }

        $baru = $this->jadwal->buatRevisi($versi, $request->user(), $data['nama_versi'], $tanggal);

        $this->log->catat(
            $request->user(),
            'revisi_jadwal',
            'Jadwal Pelajaran',
            "Membuat revisi jadwal {$baru->nama_versi} dari versi {$versi->nama_versi}.",
            'versi_jadwal',
            $baru->id_versi_jadwal
        );

        return redirect()
            ->route('admin.jadwal.index', ['semester' => $baru->id_semester, 'versi' => $baru->id_versi_jadwal])
            ->with('sukses', 'Revisi jadwal berhasil dibuat sebagai draf. Periksa lalu aktifkan bila sudah benar.');
    }

    public function create(Request $request): View
    {
        $versi = VersiJadwal::query()->with('semester')->findOrFail($request->integer('versi'));

        $this->pastikanBolehDiubah($versi, true);

        return view('admin.jadwal.create', $this->dataForm($versi) + compact('versi'));
    }

    public function storeJadwal(JadwalRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $versi = VersiJadwal::query()->with('semester')->findOrFail($data['id_versi_jadwal']);

        $this->pastikanBolehDiubah($versi);

        $this->validasiKelayakan($data, $versi);

        $data['urutan_pelajaran'] = $data['urutan_pelajaran'] ?? 1;
        $data['ruang_kelas'] = $data['ruang_kelas'] ?? null;

        $jadwal = JadwalPelajaran::create($data);

        $this->log->catat(
            $request->user(),
            'tambah_jadwal',
            'Jadwal Pelajaran',
            "Menambahkan jadwal {$jadwal->mataPelajaran->nama_mata_pelajaran} kelas {$jadwal->kelas->nama_kelas} hari {$jadwal->hari}.",
            'jadwal_pelajaran',
            $jadwal->id_jadwal
        );

        return redirect()
            ->route('admin.jadwal.index', ['semester' => $versi->id_semester, 'versi' => $versi->id_versi_jadwal])
            ->with('sukses', 'Jadwal pelajaran berhasil ditambahkan.');
    }

    public function edit(JadwalPelajaran $jadwal): View
    {
        $versi = $jadwal->versiJadwal()->with('semester')->firstOrFail();

        $this->pastikanBolehDiubah($versi, true);

        return view('admin.jadwal.edit', $this->dataForm($versi, $jadwal) + compact('versi', 'jadwal'));
    }

    public function updateJadwal(JadwalRequest $request, JadwalPelajaran $jadwal): RedirectResponse
    {
        $data = $request->validated();
        $versi = $jadwal->versiJadwal()->with('semester')->firstOrFail();

        $this->pastikanBolehDiubah($versi);
        $this->validasiKelayakan($data, $versi, $jadwal->id_jadwal);

        $data['urutan_pelajaran'] = $data['urutan_pelajaran'] ?? 1;
        $data['ruang_kelas'] = $data['ruang_kelas'] ?? null;

        $sebelum = $jadwal->toArray();
        $jadwal->update($data);

        $this->log->catat(
            $request->user(),
            'ubah_jadwal',
            'Jadwal Pelajaran',
            "Memperbarui jadwal {$jadwal->mataPelajaran->nama_mata_pelajaran} kelas {$jadwal->kelas->nama_kelas}.",
            'jadwal_pelajaran',
            $jadwal->id_jadwal,
            $sebelum,
            $jadwal->fresh()->toArray()
        );

        return redirect()
            ->route('admin.jadwal.index', ['semester' => $versi->id_semester, 'versi' => $versi->id_versi_jadwal])
            ->with('sukses', 'Jadwal pelajaran berhasil diperbarui.');
    }

    public function ubahStatusJadwal(Request $request, JadwalPelajaran $jadwal): RedirectResponse
    {
        $versi = $jadwal->versiJadwal()->with('semester')->firstOrFail();

        $this->pastikanBolehDiubah($versi);

        $sebelum = $jadwal->status_jadwal;
        $jadwal->update([
            'status_jadwal' => $sebelum === 'aktif' ? 'nonaktif' : 'aktif',
        ]);

        $this->log->catat(
            $request->user(),
            'ubah_status_jadwal',
            'Jadwal Pelajaran',
            ($jadwal->status_jadwal === 'aktif' ? 'Mengaktifkan' : 'Menonaktifkan')
                ." jadwal {$jadwal->mataPelajaran?->nama_mata_pelajaran}.",
            'jadwal_pelajaran',
            $jadwal->id_jadwal,
            ['status_jadwal' => $sebelum],
            ['status_jadwal' => $jadwal->status_jadwal]
        );

        return back()->with('sukses', 'Status jadwal berhasil diperbarui.');
    }

    /**
     * @return array{kelas: \Illuminate\Support\Collection, mataPelajaran: \Illuminate\Support\Collection, guru: \Illuminate\Support\Collection, penugasanMap: array<string, array<int, int>>}
     */
    protected function dataForm(VersiJadwal $versi, ?JadwalPelajaran $jadwal = null): array
    {
        $penugasan = PenugasanGuru::query()
            ->with('guru')
            ->where('id_semester', $versi->id_semester)
            ->where('status_aktif', true)
            ->get();

        $guru = Guru::query()
            ->where('status_guru', 'aktif')
            ->whereIn('id_guru', $penugasan->pluck('id_guru')->filter()->unique())
            ->orderBy('nama_lengkap')
            ->get();

        if ($jadwal?->guru && ! $guru->contains('id_guru', $jadwal->id_guru)) {
            $guru->push($jadwal->guru);
            $guru = $guru->sortBy('nama_lengkap')->values();
        }

        $penugasanMap = $penugasan
            ->groupBy(fn (PenugasanGuru $item) => $item->id_kelas.':'.$item->id_mata_pelajaran)
            ->map(fn ($items) => $items->pluck('id_guru')->filter()->unique()->values()->all())
            ->all();

        return [
            'kelas' => Kelas::query()
                ->where('id_tahun_ajaran', $versi->semester->id_tahun_ajaran)
                ->where('status_kelas', 'aktif')
                ->orderBy('nama_kelas')
                ->get(),
            'mataPelajaran' => MataPelajaran::query()
                ->where('status_aktif', true)
                ->orderBy('nama_mata_pelajaran')
                ->get(),
            'guru' => $guru,
            'penugasanMap' => $penugasanMap,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function validasiKelayakan(array $data, VersiJadwal $versi, ?int $abaikanId = null): void
    {
        $kelas = Kelas::find($data['id_kelas']);

        if (! $kelas || $kelas->id_tahun_ajaran !== $versi->semester->id_tahun_ajaran) {
            throw ValidationException::withMessages([
                'id_kelas' => 'Kelas tidak berasal dari tahun ajaran semester versi jadwal ini.',
            ]);
        }

        $tanggal = $versi->tanggal_berlaku->copy()->startOfDay();

        if ($tanggal->lt(now()->startOfDay())) {
            $tanggal = now()->startOfDay();
        }

        if (! $this->jadwal->guruDitugaskan(
            (int) $data['id_guru'],
            (int) $data['id_mata_pelajaran'],
            (int) $data['id_kelas'],
            $versi->id_semester,
            $tanggal,
        )) {
            throw ValidationException::withMessages([
                'id_guru' => 'Guru tersebut belum memiliki penugasan aktif untuk mata pelajaran dan kelas ini pada semester terkait.',
            ]);
        }

        $benturan = $this->jadwal->validasiBenturan($data, $abaikanId);

        if ($benturan !== []) {
            throw ValidationException::withMessages(['jadwal' => $benturan]);
        }
    }

    protected function pastikanBolehDiubah(VersiJadwal $versi, bool $dariHalamanForm = false): void
    {
        $pesan = null;

        if ($versi->status_versi !== VersiJadwal::STATUS_DRAF) {
            $pesan = 'Hanya versi jadwal berstatus draf yang dapat diubah. Buat revisi untuk menyusun jadwal baru.';
        } elseif (! $this->jadwal->versiBolehDiubah($versi)) {
            $pesan = 'Versi jadwal ini sudah memiliki sesi absensi sehingga tidak dapat diubah. Buat revisi jadwal.';
        }

        if ($pesan === null) {
            return;
        }

        if ($dariHalamanForm) {
            abort(403, $pesan);
        }

        throw ValidationException::withMessages(['versi' => $pesan]);
    }
}
