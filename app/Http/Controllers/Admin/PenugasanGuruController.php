<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\PenugasanGuru;
use App\Models\Semester;
use App\Services\LayananLogAktivitas;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PenugasanGuruController extends Controller
{
    public function __construct(private readonly LayananLogAktivitas $log)
    {
    }

    public function index(Request $request): View
    {
        $idSemester = $request->integer('semester') ?: null;
        $idGuru = $request->integer('guru') ?: null;
        $idKelas = $request->integer('kelas') ?: null;
        $status = $request->query('status');

        $penugasan = PenugasanGuru::query()
            ->with(['guru', 'mataPelajaran', 'kelas', 'semester.tahunAjaran'])
            ->when($idSemester, fn ($q) => $q->where('id_semester', $idSemester))
            ->when($idGuru, fn ($q) => $q->where('id_guru', $idGuru))
            ->when($idKelas, fn ($q) => $q->where('id_kelas', $idKelas))
            ->when(in_array($status, ['aktif', 'nonaktif'], true), fn ($q) => $q->where('status_aktif', $status === 'aktif'))
            ->orderByDesc('id_penugasan_guru')
            ->paginate(20)
            ->withQueryString();

        $semester = Semester::query()->with('tahunAjaran')->orderByDesc('tanggal_mulai')->get();
        $guru = Guru::query()->orderBy('nama_lengkap')->get();
        $kelas = Kelas::query()->orderBy('nama_kelas')->get();

        return view('admin.penugasan.index', compact(
            'penugasan',
            'semester',
            'guru',
            'kelas',
            'idSemester',
            'idGuru',
            'idKelas',
            'status',
        ));
    }

    public function create(Request $request): View
    {
        $semester = Semester::query()->with('tahunAjaran')->orderByDesc('tanggal_mulai')->get();
        $guru = Guru::query()->where('status_guru', 'aktif')->orderBy('nama_lengkap')->get();
        $mataPelajaran = MataPelajaran::query()->where('status_aktif', true)->orderBy('nama_mata_pelajaran')->get();
        $kelas = Kelas::query()->where('status_kelas', 'aktif')->orderBy('nama_kelas')->get();

        $terpilih = [
            'id_semester' => $request->integer('semester') ?: null,
            'id_guru' => $request->integer('guru') ?: null,
            'id_kelas' => $request->integer('kelas') ?: null,
        ];

        return view('admin.penugasan.create', compact('semester', 'guru', 'mataPelajaran', 'kelas', 'terpilih'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'id_guru' => ['required', 'integer', 'exists:guru,id_guru'],
            'id_mata_pelajaran' => ['required', 'integer', 'exists:mata_pelajaran,id_mata_pelajaran'],
            'id_kelas' => ['required', 'integer', 'exists:kelas,id_kelas'],
            'id_semester' => ['required', 'integer', 'exists:semester,id_semester'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
        ], [], [
            'id_guru' => 'guru',
            'id_mata_pelajaran' => 'mata pelajaran',
            'id_kelas' => 'kelas',
            'id_semester' => 'semester',
            'tanggal_mulai' => 'tanggal mulai',
            'tanggal_selesai' => 'tanggal selesai',
        ]);

        $semester = Semester::findOrFail($data['id_semester']);
        $kelas = Kelas::findOrFail($data['id_kelas']);

        if ($kelas->id_tahun_ajaran !== $semester->id_tahun_ajaran) {
            throw ValidationException::withMessages([
                'id_kelas' => 'Kelas yang dipilih tidak berasal dari tahun ajaran semester tersebut.',
            ]);
        }

        $tanggalMulai = Carbon::parse($data['tanggal_mulai'])->startOfDay();
        $awalSemester = $semester->tanggal_mulai?->copy()->startOfDay();
        $akhirSemester = $semester->tanggal_selesai?->copy()->endOfDay();

        if ($awalSemester && $tanggalMulai->lt($awalSemester)) {
            throw ValidationException::withMessages([
                'tanggal_mulai' => 'Tanggal mulai tidak boleh sebelum semester dimulai.',
            ]);
        }

        if ($akhirSemester && $tanggalMulai->gt($akhirSemester)) {
            throw ValidationException::withMessages([
                'tanggal_mulai' => 'Tanggal mulai tidak boleh setelah semester berakhir.',
            ]);
        }

        if (! empty($data['tanggal_selesai'])) {
            $tanggalSelesai = Carbon::parse($data['tanggal_selesai'])->startOfDay();

            if ($awalSemester && $tanggalSelesai->lt($awalSemester)) {
                throw ValidationException::withMessages([
                    'tanggal_selesai' => 'Tanggal selesai tidak boleh sebelum semester dimulai.',
                ]);
            }

            if ($akhirSemester && $tanggalSelesai->gt($akhirSemester)) {
                throw ValidationException::withMessages([
                    'tanggal_selesai' => 'Tanggal selesai tidak boleh setelah semester berakhir.',
                ]);
            }
        }

        $penugasan = DB::transaction(function () use ($data, $request, $semester, $kelas) {
            $penugasan = PenugasanGuru::query()->firstOrNew([
                'id_guru' => $data['id_guru'],
                'id_mata_pelajaran' => $data['id_mata_pelajaran'],
                'id_kelas' => $data['id_kelas'],
                'id_semester' => $data['id_semester'],
            ]);

            $baru = ! $penugasan->exists;

            $penugasan->fill([
                'tanggal_mulai' => $data['tanggal_mulai'],
                'tanggal_selesai' => $data['tanggal_selesai'] ?? null,
                'status_aktif' => true,
            ])->save();

            $this->log->catat(
                $request->user(),
                $baru ? 'tambah_penugasan' : 'aktifkan_penugasan',
                'Penugasan Guru',
                ($baru ? 'Menambahkan' : 'Mengaktifkan kembali')
                    ." penugasan {$penugasan->guru->nama_lengkap} untuk {$penugasan->mataPelajaran->nama_mata_pelajaran} di {$kelas->nama_kelas}.",
                'penugasan_guru',
                $penugasan->id_penugasan_guru
            );

            return $penugasan;
        });

        return redirect()
            ->route('admin.penugasan.index', ['semester' => $semester->id_semester])
            ->with('sukses', 'Penugasan guru berhasil disimpan.');
    }

    public function ubahStatus(Request $request, PenugasanGuru $penugasan): RedirectResponse
    {
        $sebelum = $penugasan->status_aktif;
        $penugasan->update(['status_aktif' => ! $sebelum]);

        $this->log->catat(
            $request->user(),
            $penugasan->status_aktif ? 'aktifkan_penugasan' : 'nonaktifkan_penugasan',
            'Penugasan Guru',
            ($penugasan->status_aktif ? 'Mengaktifkan' : 'Menonaktifkan')
                ." penugasan {$penugasan->guru?->nama_lengkap}.",
            'penugasan_guru',
            $penugasan->id_penugasan_guru,
            ['status_aktif' => $sebelum],
            ['status_aktif' => $penugasan->status_aktif]
        );

        return back()->with('sukses', 'Status penugasan berhasil diperbarui.');
    }
}
