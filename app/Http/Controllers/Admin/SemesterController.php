<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SemesterRequest;
use App\Models\Semester;
use App\Models\TahunAjaran;
use App\Services\LayananLogAktivitas;
use App\Services\LayananPeriode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SemesterController extends Controller
{
    public function __construct(
        private readonly LayananLogAktivitas $log,
        private readonly LayananPeriode $periode,
    ) {
    }

    public function index(Request $request): View
    {
        $idTahun = $request->integer('tahun') ?: null;

        $semester = Semester::query()
            ->with('tahunAjaran')
            ->withCount(['penugasanGuru', 'versiJadwal', 'jadwalPiket'])
            ->when($idTahun, fn ($q) => $q->where('id_tahun_ajaran', $idTahun))
            ->orderByDesc('id_tahun_ajaran')
            ->orderBy('nama_semester')
            ->paginate(12)
            ->withQueryString();

        $tahunAjaran = TahunAjaran::query()->orderByDesc('tanggal_mulai')->get();
        $periodeBerjalan = $this->periode->aktif();
        $semesterDitandaiAktif = Semester::query()
            ->with('tahunAjaran')
            ->where('status_aktif', true)
            ->orderByDesc('tanggal_mulai')
            ->first();

        return view('admin.semester.index', compact(
            'semester',
            'tahunAjaran',
            'idTahun',
            'periodeBerjalan',
            'semesterDitandaiAktif',
        ));
    }

    public function create(): View
    {
        $tahunAjaran = TahunAjaran::query()->orderByDesc('tanggal_mulai')->get();

        return view('admin.semester.create', compact('tahunAjaran'));
    }

    public function store(SemesterRequest $request): RedirectResponse
    {
        $semester = DB::transaction(function () use ($request) {
            if ($request->boolean('status_aktif')) {
                Semester::query()->where('status_aktif', true)->update(['status_aktif' => false]);
                TahunAjaran::query()->where('id_tahun_ajaran', '!=', $request->integer('id_tahun_ajaran'))
                    ->where('status_aktif', true)
                    ->update(['status_aktif' => false]);
                TahunAjaran::query()->where('id_tahun_ajaran', $request->integer('id_tahun_ajaran'))
                    ->update(['status_aktif' => true]);
            }

            $semester = Semester::create($request->validated());

            $this->log->catat(
                $request->user(),
                'tambah_semester',
                'Semester',
                "Menambahkan semester {$semester->nama_semester}.",
                'semester',
                $semester->id_semester
            );

            return $semester;
        });

        return redirect()
            ->route('admin.semester.index')
            ->with('sukses', 'Data semester berhasil ditambahkan.');
    }

    public function edit(Semester $semester): View
    {
        $semester->load('tahunAjaran');
        $tahunAjaran = TahunAjaran::query()->orderByDesc('tanggal_mulai')->get();

        return view('admin.semester.edit', compact('semester', 'tahunAjaran'));
    }

    public function update(SemesterRequest $request, Semester $semester): RedirectResponse
    {
        $sebelum = $semester->toArray();

        DB::transaction(function () use ($request, $semester, $sebelum) {
            if ($request->boolean('status_aktif')) {
                Semester::query()
                    ->where('id_semester', '!=', $semester->id_semester)
                    ->where('status_aktif', true)
                    ->update(['status_aktif' => false]);

                TahunAjaran::query()->where('status_aktif', true)
                    ->where('id_tahun_ajaran', '!=', $request->integer('id_tahun_ajaran'))
                    ->update(['status_aktif' => false]);

                TahunAjaran::query()->where('id_tahun_ajaran', $request->integer('id_tahun_ajaran'))
                    ->update(['status_aktif' => true]);
            }

            $semester->update($request->validated());

            $this->log->catat(
                $request->user(),
                'ubah_semester',
                'Semester',
                "Memperbarui semester {$semester->nama_semester}.",
                'semester',
                $semester->id_semester,
                $sebelum,
                $semester->fresh()->toArray()
            );
        });

        return redirect()
            ->route('admin.semester.index')
            ->with('sukses', 'Data semester berhasil diperbarui.');
    }
}
