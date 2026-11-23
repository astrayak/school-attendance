<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MataPelajaranRequest;
use App\Models\MataPelajaran;
use App\Services\LayananLogAktivitas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MataPelajaranController extends Controller
{
    public function __construct(private readonly LayananLogAktivitas $log)
    {
    }

    public function index(Request $request): View
    {
        $pencarian = trim((string) $request->query('q'));
        $status = $request->query('status');

        $mataPelajaran = MataPelajaran::query()
            ->withCount(['penugasanGuru', 'jadwal'])
            ->when($pencarian !== '', function ($q) use ($pencarian) {
                $q->where(function ($qq) use ($pencarian) {
                    $qq->where('nama_mata_pelajaran', 'like', "%{$pencarian}%")
                        ->orWhere('kode_mata_pelajaran', 'like', "%{$pencarian}%");
                });
            })
            ->when(in_array($status, ['aktif', 'nonaktif'], true), fn ($q) => $q->where('status_aktif', $status === 'aktif'))
            ->orderBy('nama_mata_pelajaran')
            ->paginate(15)
            ->withQueryString();

        return view('admin.mata-pelajaran.index', compact('mataPelajaran', 'pencarian', 'status'));
    }

    public function create(): View
    {
        return view('admin.mata-pelajaran.create');
    }

    public function store(MataPelajaranRequest $request): RedirectResponse
    {
        $mapel = MataPelajaran::create($request->validated());

        $this->log->catat(
            $request->user(),
            'tambah_mapel',
            'Mata Pelajaran',
            "Menambahkan mata pelajaran {$mapel->nama_mata_pelajaran}.",
            'mata_pelajaran',
            $mapel->id_mata_pelajaran
        );

        return redirect()
            ->route('admin.mata-pelajaran.index')
            ->with('sukses', 'Mata pelajaran berhasil ditambahkan.');
    }

    public function edit(MataPelajaran $mataPelajaran): View
    {
        return view('admin.mata-pelajaran.edit', compact('mataPelajaran'));
    }

    public function update(MataPelajaranRequest $request, MataPelajaran $mataPelajaran): RedirectResponse
    {
        $sebelum = $mataPelajaran->toArray();
        $mataPelajaran->update($request->validated());

        $this->log->catat(
            $request->user(),
            'ubah_mapel',
            'Mata Pelajaran',
            "Memperbarui mata pelajaran {$mataPelajaran->nama_mata_pelajaran}.",
            'mata_pelajaran',
            $mataPelajaran->id_mata_pelajaran,
            $sebelum,
            $mataPelajaran->fresh()->toArray()
        );

        return redirect()
            ->route('admin.mata-pelajaran.index')
            ->with('sukses', 'Mata pelajaran berhasil diperbarui.');
    }
}
