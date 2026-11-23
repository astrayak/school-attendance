<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\JadwalGuruPiket;
use App\Models\Semester;
use App\Services\LayananLogAktivitas;
use App\Support\Status;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GuruPiketController extends Controller
{
    public function __construct(private readonly LayananLogAktivitas $log)
    {
    }

    public function index(Request $request): View
    {
        $idSemester = $request->integer('semester') ?: null;
        $idGuru = $request->integer('guru') ?: null;
        $hari = $request->query('hari');
        $hari = array_key_exists((string) $hari, Status::HARI) ? $hari : null;
        $status = $request->query('status');

        $piket = JadwalGuruPiket::query()
            ->with(['guru', 'semester.tahunAjaran'])
            ->when($idSemester, fn ($q) => $q->where('id_semester', $idSemester))
            ->when($idGuru, fn ($q) => $q->where('id_guru', $idGuru))
            ->when($hari, fn ($q) => $q->where('hari', $hari))
            ->when(in_array($status, ['aktif', 'nonaktif'], true), fn ($q) => $q->where('status_aktif', $status === 'aktif'))
            ->orderByRaw("FIELD(hari, 'senin','selasa','rabu','kamis','jumat','sabtu','minggu')")
            ->orderByDesc('tanggal_mulai_berlaku')
            ->paginate(20)
            ->withQueryString();

        $semester = Semester::query()->with('tahunAjaran')->orderByDesc('tanggal_mulai')->get();
        $guru = Guru::query()->where('status_guru', 'aktif')->orderBy('nama_lengkap')->get();

        return view('admin.piket.index', compact('piket', 'semester', 'guru', 'idSemester', 'idGuru', 'hari', 'status'));
    }

    public function create(Request $request): View
    {
        $semester = Semester::query()->with('tahunAjaran')->orderByDesc('tanggal_mulai')->get();
        $guru = Guru::query()->where('status_guru', 'aktif')->orderBy('nama_lengkap')->get();

        $terpilih = [
            'id_semester' => $request->integer('semester') ?: null,
            'id_guru' => $request->integer('guru') ?: null,
        ];

        return view('admin.piket.create', compact('semester', 'guru', 'terpilih'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'id_guru' => ['required', 'integer', 'exists:guru,id_guru'],
            'id_semester' => ['required', 'integer', 'exists:semester,id_semester'],
            'hari' => ['required', 'in:'.implode(',', array_keys(Status::HARI))],
            'tanggal_mulai_berlaku' => ['required', 'date'],
            'tanggal_berakhir' => ['nullable', 'date', 'after_or_equal:tanggal_mulai_berlaku'],
        ], [], [
            'id_guru' => 'guru',
            'id_semester' => 'semester',
            'hari' => 'hari',
            'tanggal_mulai_berlaku' => 'tanggal mulai berlaku',
            'tanggal_berakhir' => 'tanggal berakhir',
        ]);

        $semester = Semester::findOrFail($data['id_semester']);

        if ($data['tanggal_mulai_berlaku'] < $semester->tanggal_mulai->toDateString()) {
            throw ValidationException::withMessages([
                'tanggal_mulai_berlaku' => 'Tanggal mulai berlaku tidak boleh sebelum semester dimulai.',
            ]);
        }

        $bentrok = JadwalGuruPiket::query()
            ->where('id_guru', $data['id_guru'])
            ->where('id_semester', $data['id_semester'])
            ->where('hari', $data['hari'])
            ->where('status_aktif', true)
            ->whereDate('tanggal_mulai_berlaku', '<=', $data['tanggal_berakhir'] ?? '9999-12-31')
            ->where(function ($q) use ($data) {
                $q->whereNull('tanggal_berakhir')
                    ->orWhereDate('tanggal_berakhir', '>=', $data['tanggal_mulai_berlaku']);
            })
            ->exists();

        if ($bentrok) {
            throw ValidationException::withMessages([
                'tanggal_mulai_berlaku' => 'Guru ini sudah memiliki jadwal piket pada hari yang sama di rentang tanggal tersebut.',
            ]);
        }

        $piket = JadwalGuruPiket::create([
            'id_guru' => $data['id_guru'],
            'hari' => $data['hari'],
            'tanggal_mulai_berlaku' => $data['tanggal_mulai_berlaku'],
            'tanggal_berakhir' => $data['tanggal_berakhir'] ?? null,
            'id_semester' => $data['id_semester'],
            'status_aktif' => true,
        ]);

        $this->log->catat(
            $request->user(),
            'tambah_jadwal_piket',
            'Guru Piket',
            "Menambahkan jadwal piket {$piket->guru->nama_lengkap} hari {$piket->hari}.",
            'jadwal_guru_piket',
            $piket->id_jadwal_piket
        );

        return redirect()
            ->route('admin.piket.index', ['semester' => $semester->id_semester])
            ->with('sukses', 'Jadwal guru piket berhasil ditambahkan.');
    }

    public function ubahStatus(Request $request, JadwalGuruPiket $piket): RedirectResponse
    {
        $sebelum = $piket->status_aktif;
        $piket->update(['status_aktif' => ! $sebelum]);

        $this->log->catat(
            $request->user(),
            $piket->status_aktif ? 'aktifkan_piket' : 'nonaktifkan_piket',
            'Guru Piket',
            ($piket->status_aktif ? 'Mengaktifkan' : 'Menonaktifkan')
                ." jadwal piket {$piket->guru?->nama_lengkap} hari {$piket->hari}.",
            'jadwal_guru_piket',
            $piket->id_jadwal_piket,
            ['status_aktif' => $sebelum],
            ['status_aktif' => $piket->status_aktif]
        );

        return back()->with('sukses', 'Status jadwal piket berhasil diperbarui.');
    }
}
