<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\KelasRequest;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use App\Services\LayananLogAktivitas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KelasController extends Controller
{
    public function __construct(private readonly LayananLogAktivitas $log)
    {
    }

    public function index(Request $request): View
    {
        $pencarian = trim((string) $request->query('q'));
        $idTahun = $request->integer('tahun') ?: null;

        $kelas = Kelas::query()
            ->with(['waliKelas', 'tahunAjaran'])
            ->withCount(['riwayatSiswa as jumlah_siswa' => fn ($q) => $q->where('status_aktif', true)])
            ->when($pencarian !== '', fn ($q) => $q->where('nama_kelas', 'like', "%{$pencarian}%"))
            ->when($idTahun, fn ($q) => $q->where('id_tahun_ajaran', $idTahun))
            ->orderByDesc('id_tahun_ajaran')
            ->orderBy('tingkat')
            ->orderBy('nama_kelas')
            ->paginate(15)
            ->withQueryString();

        $tahunAjaran = TahunAjaran::query()->orderByDesc('tanggal_mulai')->get();

        return view('admin.kelas.index', compact('kelas', 'tahunAjaran', 'pencarian', 'idTahun'));
    }

    public function create(): View
    {
        $tahunAjaran = TahunAjaran::query()->orderByDesc('tanggal_mulai')->get();
        $guru = Guru::query()->where('status_guru', 'aktif')->orderBy('nama_lengkap')->get();

        return view('admin.kelas.create', compact('tahunAjaran', 'guru'));
    }

    public function store(KelasRequest $request): RedirectResponse
    {
        $kelas = Kelas::create($request->validated());

        $this->log->catat(
            $request->user(),
            'tambah_kelas',
            'Data Kelas',
            "Menambahkan kelas {$kelas->nama_kelas}.",
            'kelas',
            $kelas->id_kelas
        );

        return redirect()
            ->route('admin.kelas.index')
            ->with('sukses', 'Data kelas berhasil ditambahkan.');
    }

    public function edit(Kelas $kelas): View
    {
        $tahunAjaran = TahunAjaran::query()->orderByDesc('tanggal_mulai')->get();
        $guru = Guru::query()->where('status_guru', 'aktif')->orderBy('nama_lengkap')->get();

        return view('admin.kelas.edit', compact('kelas', 'tahunAjaran', 'guru'));
    }

    public function update(KelasRequest $request, Kelas $kelas): RedirectResponse
    {
        $sebelum = $kelas->toArray();
        $kelas->update($request->validated());

        $this->log->catat(
            $request->user(),
            'ubah_kelas',
            'Data Kelas',
            "Memperbarui kelas {$kelas->nama_kelas}.",
            'kelas',
            $kelas->id_kelas,
            $sebelum,
            $kelas->fresh()->toArray()
        );

        return redirect()
            ->route('admin.kelas.index')
            ->with('sukses', 'Data kelas berhasil diperbarui.');
    }
}
