<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\GuruRequest;
use App\Models\Guru;
use App\Models\JadwalGuruPiket;
use App\Models\Pengguna;
use App\Models\SesiAbsensi;
use App\Services\LayananLogAktivitas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GuruController extends Controller
{
    public function __construct(private readonly LayananLogAktivitas $log)
    {
    }

    public function index(Request $request): View
    {
        $pencarian = trim((string) $request->query('q'));
        $status = $request->query('status');

        $guru = Guru::query()
            ->with('pengguna')
            ->withCount(['penugasan', 'jadwal', 'jadwalPiket'])
            ->when($pencarian !== '', function ($q) use ($pencarian) {
                $q->where(function ($qq) use ($pencarian) {
                    $qq->where('nama_lengkap', 'like', "%{$pencarian}%")
                        ->orWhere('nip', 'like', "%{$pencarian}%")
                        ->orWhere('nuptk', 'like', "%{$pencarian}%")
                        ->orWhereHas('pengguna', fn ($p) => $p
                            ->where('email', 'like', "%{$pencarian}%")
                            ->orWhere('nama_pengguna', 'like', "%{$pencarian}%"));
                });
            })
            ->when(in_array($status, ['aktif', 'nonaktif'], true), fn ($q) => $q->where('status_guru', $status))
            ->orderBy('nama_lengkap')
            ->paginate(15)
            ->withQueryString();

        return view('admin.guru.index', compact('guru', 'pencarian', 'status'));
    }

    public function create(): View
    {
        return view('admin.guru.create');
    }

    public function store(GuruRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $guru = DB::transaction(function () use ($data, $request) {
            $pengguna = Pengguna::create([
                'nama_pengguna' => $data['nama_pengguna'],
                'email' => $data['email'],
                'kata_sandi' => $data['kata_sandi'],
                'peran' => 'guru',
                'status_aktif' => $data['status_aktif'],
            ]);

            $guru = Guru::create([
                'id_pengguna' => $pengguna->id_pengguna,
                'nip' => $data['nip'] ?? null,
                'nuptk' => $data['nuptk'] ?? null,
                'nama_lengkap' => $data['nama_lengkap'],
                'jenis_kelamin' => $data['jenis_kelamin'],
                'tempat_lahir' => $data['tempat_lahir'] ?? null,
                'tanggal_lahir' => $data['tanggal_lahir'] ?? null,
                'nomor_telepon' => $data['nomor_telepon'] ?? null,
                'alamat' => $data['alamat'] ?? null,
                'status_guru' => $data['status_guru'],
            ]);

            $this->log->catat(
                $request->user(),
                'tambah_guru',
                'Data Guru',
                "Menambahkan guru {$guru->nama_lengkap}.",
                'guru',
                $guru->id_guru
            );

            return $guru;
        });

        return redirect()
            ->route('admin.guru.show', $guru)
            ->with('sukses', 'Data guru berhasil ditambahkan.');
    }

    public function show(Guru $guru): View
    {
        $guru->load(['pengguna', 'kelasWali.tahunAjaran']);

        $penugasan = $guru->penugasan()
            ->with(['mataPelajaran', 'kelas', 'semester.tahunAjaran'])
            ->orderByDesc('tanggal_mulai')
            ->get();

        $piket = JadwalGuruPiket::query()
            ->with('semester')
            ->where('id_guru', $guru->id_guru)
            ->orderByDesc('tanggal_mulai_berlaku')
            ->limit(10)
            ->get();

        $sesi = SesiAbsensi::query()
            ->with(['jadwal.kelas', 'jadwal.mataPelajaran', 'guruPelaksana'])
            ->where(function ($q) use ($guru) {
                $q->where('id_guru_penanggung_jawab', $guru->id_guru)
                    ->orWhere('id_guru_pelaksana', $guru->id_guru);
            })
            ->orderByDesc('tanggal_pelajaran')
            ->orderByDesc('waktu_mulai')
            ->limit(10)
            ->get();

        $jumlahSesi = SesiAbsensi::query()
            ->where(function ($q) use ($guru) {
                $q->where('id_guru_penanggung_jawab', $guru->id_guru)
                    ->orWhere('id_guru_pelaksana', $guru->id_guru);
            })
            ->count();

        return view('admin.guru.show', compact('guru', 'penugasan', 'piket', 'sesi', 'jumlahSesi'));
    }

    public function edit(Guru $guru): View
    {
        $guru->load('pengguna');

        return view('admin.guru.edit', compact('guru'));
    }

    public function update(GuruRequest $request, Guru $guru): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $request, $guru) {
            $sebelum = $guru->load('pengguna')->toArray();

            $penggunaData = [
                'nama_pengguna' => $data['nama_pengguna'],
                'email' => $data['email'],
                'status_aktif' => $data['status_aktif'],
            ];

            if (! empty($data['kata_sandi'])) {
                $penggunaData['kata_sandi'] = $data['kata_sandi'];
            }

            $guru->pengguna->update($penggunaData);

            $guru->update([
                'nip' => $data['nip'] ?? null,
                'nuptk' => $data['nuptk'] ?? null,
                'nama_lengkap' => $data['nama_lengkap'],
                'jenis_kelamin' => $data['jenis_kelamin'],
                'tempat_lahir' => $data['tempat_lahir'] ?? null,
                'tanggal_lahir' => $data['tanggal_lahir'] ?? null,
                'nomor_telepon' => $data['nomor_telepon'] ?? null,
                'alamat' => $data['alamat'] ?? null,
                'status_guru' => $data['status_guru'],
            ]);

            $this->log->catat(
                $request->user(),
                'ubah_guru',
                'Data Guru',
                "Memperbarui data guru {$guru->nama_lengkap}.",
                'guru',
                $guru->id_guru,
                $sebelum,
                $guru->fresh('pengguna')->toArray()
            );
        });

        return redirect()
            ->route('admin.guru.show', $guru)
            ->with('sukses', 'Data guru berhasil diperbarui.');
    }
}
