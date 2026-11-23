<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TahunAjaranRequest;
use App\Models\TahunAjaran;
use App\Services\LayananLogAktivitas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TahunAjaranController extends Controller
{
    public function __construct(private readonly LayananLogAktivitas $log)
    {
    }

    public function index(): View
    {
        $tahunAjaran = TahunAjaran::query()
            ->withCount(['semester', 'kelas', 'riwayatKelas'])
            ->orderByDesc('tanggal_mulai')
            ->paginate(10);

        return view('admin.tahun-ajaran.index', compact('tahunAjaran'));
    }

    public function create(): View
    {
        return view('admin.tahun-ajaran.create');
    }

    public function store(TahunAjaranRequest $request): RedirectResponse
    {
        $tahun = DB::transaction(function () use ($request) {
            if ($request->boolean('status_aktif')) {
                TahunAjaran::query()->where('status_aktif', true)->update(['status_aktif' => false]);
            }

            $tahun = TahunAjaran::create($request->validated());

            $this->log->catat(
                $request->user(),
                'tambah_tahun_ajaran',
                'Tahun Ajaran',
                "Menambahkan tahun ajaran {$tahun->nama_tahun_ajaran}.",
                'tahun_ajaran',
                $tahun->id_tahun_ajaran
            );

            return $tahun;
        });

        return redirect()
            ->route('admin.tahun-ajaran.index')
            ->with('sukses', "Tahun ajaran {$tahun->nama_tahun_ajaran} berhasil ditambahkan.");
    }

    public function edit(TahunAjaran $tahunAjaran): View
    {
        return view('admin.tahun-ajaran.edit', compact('tahunAjaran'));
    }

    public function update(TahunAjaranRequest $request, TahunAjaran $tahunAjaran): RedirectResponse
    {
        $sebelum = $tahunAjaran->toArray();

        DB::transaction(function () use ($request, $tahunAjaran, $sebelum) {
            if ($request->boolean('status_aktif')) {
                TahunAjaran::query()
                    ->where('id_tahun_ajaran', '!=', $tahunAjaran->id_tahun_ajaran)
                    ->where('status_aktif', true)
                    ->update(['status_aktif' => false]);
            }

            $tahunAjaran->update($request->validated());

            $this->log->catat(
                $request->user(),
                'ubah_tahun_ajaran',
                'Tahun Ajaran',
                "Memperbarui tahun ajaran {$tahunAjaran->nama_tahun_ajaran}.",
                'tahun_ajaran',
                $tahunAjaran->id_tahun_ajaran,
                $sebelum,
                $tahunAjaran->fresh()->toArray()
            );
        });

        return redirect()
            ->route('admin.tahun-ajaran.index')
            ->with('sukses', 'Tahun ajaran berhasil diperbarui.');
    }
}
