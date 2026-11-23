<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\PetugasKonfirmasi;
use App\Models\Semester;
use App\Models\Siswa;
use App\Services\LayananLogAktivitas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PetugasKonfirmasiController extends Controller
{
    public function __construct(private readonly LayananLogAktivitas $log)
    {
    }

    public function index(Request $request): View
    {
        $idSemester = $request->integer('semester') ?: null;
        $idKelas = $request->integer('kelas') ?: null;
        $status = $request->query('status');

        $petugas = PetugasKonfirmasi::query()
            ->with(['siswa.pengguna', 'kelas', 'semester.tahunAjaran'])
            ->when($idSemester, fn ($q) => $q->where('id_semester', $idSemester))
            ->when($idKelas, fn ($q) => $q->where('id_kelas', $idKelas))
            ->when(in_array($status, ['aktif', 'nonaktif'], true), fn ($q) => $q->where('status_aktif', $status === 'aktif'))
            ->orderBy('id_kelas')
            ->orderBy('urutan_prioritas')
            ->paginate(25)
            ->withQueryString();

        $semester = Semester::query()->with('tahunAjaran')->orderByDesc('tanggal_mulai')->get();
        $kelas = Kelas::query()->orderBy('nama_kelas')->get();

        return view('admin.petugas-konfirmasi.index', compact(
            'petugas',
            'semester',
            'kelas',
            'idSemester',
            'idKelas',
            'status',
        ));
    }

    public function create(Request $request): View
    {
        $idSemester = $request->integer('semester') ?: null;
        $idKelas = $request->integer('kelas') ?: null;

        $semester = Semester::query()->with('tahunAjaran')->orderByDesc('tanggal_mulai')->get();
        $kelas = Kelas::query()->where('status_kelas', 'aktif')->orderBy('nama_kelas')->get();

        $siswa = collect();
        $terdaftar = collect();

        if ($idKelas && $idSemester) {
            $siswa = Siswa::query()
                ->with('pengguna')
                ->where('status_siswa', 'aktif')
                ->whereHas('riwayatKelas', function ($q) use ($idKelas) {
                    $q->where('id_kelas', $idKelas)
                        ->whereDate('tanggal_mulai', '<=', now()->toDateString())
                        ->where(function ($qq) {
                            $qq->whereNull('tanggal_selesai')
                                ->orWhereDate('tanggal_selesai', '>=', now()->toDateString());
                        });
                })
                ->orderBy('nama_lengkap')
                ->get();

            $terdaftar = PetugasKonfirmasi::query()
                ->where('id_kelas', $idKelas)
                ->where('id_semester', $idSemester)
                ->get()
                ->keyBy('id_siswa');
        }

        return view('admin.petugas-konfirmasi.create', compact(
            'semester',
            'kelas',
            'siswa',
            'terdaftar',
            'idSemester',
            'idKelas',
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'id_kelas' => ['required', 'integer', 'exists:kelas,id_kelas'],
            'id_semester' => ['required', 'integer', 'exists:semester,id_semester'],
            'urutan' => ['required', 'array'],
            'urutan.*' => ['nullable', 'integer', 'min:1', 'max:99'],
        ], [], [
            'id_kelas' => 'kelas',
            'id_semester' => 'semester',
            'urutan' => 'prioritas petugas',
            'urutan.*' => 'urutan prioritas',
        ]);

        $kelas = Kelas::findOrFail($data['id_kelas']);
        $semester = Semester::findOrFail($data['id_semester']);

        if ($kelas->id_tahun_ajaran !== $semester->id_tahun_ajaran) {
            throw ValidationException::withMessages([
                'id_kelas' => 'Kelas dan semester harus berasal dari tahun ajaran yang sama.',
            ]);
        }

        $urutan = collect($data['urutan'])
            ->filter(fn ($nilai) => $nilai !== null && $nilai !== '')
            ->map(fn ($nilai) => (int) $nilai);

        if ($urutan->isEmpty()) {
            throw ValidationException::withMessages([
                'urutan' => 'Pilih minimal satu siswa dan tentukan urutan prioritasnya.',
            ]);
        }

        if ($urutan->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages([
                'urutan' => 'Urutan prioritas antar siswa tidak boleh sama.',
            ]);
        }

        $idSiswa = $urutan->keys()->map(fn ($id) => (int) $id);

        $valid = Siswa::query()
            ->whereIn('id_siswa', $idSiswa)
            ->where('status_siswa', 'aktif')
            ->whereHas('riwayatKelas', function ($q) use ($kelas) {
                $q->where('id_kelas', $kelas->id_kelas)
                    ->whereDate('tanggal_mulai', '<=', now()->toDateString())
                    ->where(function ($qq) {
                        $qq->whereNull('tanggal_selesai')
                            ->orWhereDate('tanggal_selesai', '>=', now()->toDateString());
                    });
            })
            ->pluck('id_siswa');

        if ($valid->count() !== $idSiswa->count()) {
            throw ValidationException::withMessages([
                'urutan' => 'Ada siswa yang tidak aktif atau tidak berada di kelas tersebut saat ini.',
            ]);
        }

        foreach ($urutan as $id => $prioritas) {
            PetugasKonfirmasi::query()->updateOrCreate(
                [
                    'id_siswa' => (int) $id,
                    'id_kelas' => $kelas->id_kelas,
                    'id_semester' => $semester->id_semester,
                ],
                [
                    'urutan_prioritas' => $prioritas,
                    'tanggal_mulai' => now()->toDateString(),
                    'tanggal_selesai' => null,
                    'status_aktif' => true,
                ]
            );
        }

        $this->log->catat(
            $request->user(),
            'tetapkan_petugas_konfirmasi',
            'Petugas Konfirmasi',
            "Menetapkan {$urutan->count()} petugas konfirmasi untuk kelas {$kelas->nama_kelas}.",
            'petugas_konfirmasi',
            $kelas->id_kelas
        );

        return redirect()
            ->route('admin.petugas-konfirmasi.index', [
                'semester' => $semester->id_semester,
                'kelas' => $kelas->id_kelas,
            ])
            ->with('sukses', 'Petugas konfirmasi berhasil disimpan.');
    }

    public function ubahStatus(Request $request, PetugasKonfirmasi $petugas): RedirectResponse
    {
        $sebelum = $petugas->status_aktif;
        $petugas->update([
            'status_aktif' => ! $sebelum,
            'tanggal_selesai' => $sebelum ? now()->toDateString() : null,
        ]);

        $this->log->catat(
            $request->user(),
            $petugas->status_aktif ? 'aktifkan_petugas' : 'nonaktifkan_petugas',
            'Petugas Konfirmasi',
            ($petugas->status_aktif ? 'Mengaktifkan' : 'Menonaktifkan')
                ." petugas konfirmasi {$petugas->siswa?->nama_lengkap}.",
            'petugas_konfirmasi',
            $petugas->id_petugas_konfirmasi,
            ['status_aktif' => $sebelum],
            ['status_aktif' => $petugas->status_aktif]
        );

        return back()->with('sukses', 'Status petugas konfirmasi berhasil diperbarui.');
    }
}
