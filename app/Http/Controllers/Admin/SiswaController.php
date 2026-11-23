<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SiswaRequest;
use App\Models\CatatanAbsensi;
use App\Models\Kelas;
use App\Models\KodeQrSiswa;
use App\Models\Pengguna;
use App\Models\RiwayatKelasSiswa;
use App\Models\Semester;
use App\Models\SesiAbsensi;
use App\Models\Siswa;
use App\Services\LayananLogAktivitas;
use App\Services\LayananQrCode;
use App\Services\LayananRekapAbsensi;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SiswaController extends Controller
{
    public function __construct(
        private readonly LayananQrCode $qr,
        private readonly LayananLogAktivitas $log,
        private readonly LayananRekapAbsensi $rekap,
    ) {
    }

    public function index(Request $request): View
    {
        $pencarian = trim((string) $request->query('q'));
        $idKelas = $request->integer('kelas') ?: null;
        $status = $request->query('status');

        $siswa = Siswa::query()
            ->with(['pengguna', 'riwayatKelasAktif.kelas'])
            ->when($pencarian !== '', function ($q) use ($pencarian) {
                $q->where(function ($qq) use ($pencarian) {
                    $qq->where('nama_lengkap', 'like', "%{$pencarian}%")
                        ->orWhere('nis', 'like', "%{$pencarian}%")
                        ->orWhere('nisn', 'like', "%{$pencarian}%")
                        ->orWhereHas('pengguna', fn ($p) => $p->where('email', 'like', "%{$pencarian}%"));
                });
            })
            ->when($idKelas, fn ($q) => $q->whereHas('riwayatKelasAktif', fn ($r) => $r->where('id_kelas', $idKelas)))
            ->when(in_array($status, ['aktif', 'lulus', 'pindah', 'nonaktif'], true), fn ($q) => $q->where('status_siswa', $status))
            ->orderBy('nama_lengkap')
            ->paginate(15)
            ->withQueryString();

        $kelas = Kelas::query()
            ->where('status_kelas', 'aktif')
            ->orderBy('nama_kelas')
            ->get();

        return view('admin.siswa.index', compact('siswa', 'kelas', 'pencarian', 'idKelas', 'status'));
    }

    public function create(): View
    {
        $kelas = $this->daftarKelas();

        return view('admin.siswa.create', compact('kelas'));
    }

    public function store(SiswaRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $siswa = DB::transaction(function () use ($data, $request) {
            $pengguna = Pengguna::create([
                'nama_pengguna' => $data['nama_pengguna'],
                'email' => $data['email'],
                'kata_sandi' => $data['kata_sandi'],
                'peran' => 'siswa',
                'status_aktif' => $data['status_aktif'],
            ]);

            $siswa = Siswa::create([
                'id_pengguna' => $pengguna->id_pengguna,
                'nis' => $data['nis'],
                'nisn' => $data['nisn'] ?? null,
                'nama_lengkap' => $data['nama_lengkap'],
                'jenis_kelamin' => $data['jenis_kelamin'],
                'tempat_lahir' => $data['tempat_lahir'] ?? null,
                'tanggal_lahir' => $data['tanggal_lahir'] ?? null,
                'agama' => $data['agama'] ?? null,
                'alamat' => $data['alamat'] ?? null,
                'nomor_telepon' => $data['nomor_telepon'] ?? null,
                'nama_orang_tua' => $data['nama_orang_tua'] ?? null,
                'nomor_telepon_orang_tua' => $data['nomor_telepon_orang_tua'] ?? null,
                'status_siswa' => $data['status_siswa'],
            ]);

            if (! empty($data['id_kelas'])) {
                $kelas = Kelas::findOrFail($data['id_kelas']);
                $mulai = isset($data['tanggal_mulai_kelas'])
                    ? Carbon::parse($data['tanggal_mulai_kelas'])
                    : now()->startOfDay();

                RiwayatKelasSiswa::create([
                    'id_siswa' => $siswa->id_siswa,
                    'id_kelas' => $kelas->id_kelas,
                    'id_tahun_ajaran' => $kelas->id_tahun_ajaran,
                    'tanggal_mulai' => $mulai->toDateString(),
                    'status_aktif' => true,
                ]);
            }

            $this->qr->buat($siswa, $request->user());

            $this->log->catat(
                $request->user(),
                'tambah_siswa',
                'Data Siswa',
                "Menambahkan siswa {$siswa->nama_lengkap} ({$siswa->nis}).",
                'siswa',
                $siswa->id_siswa
            );

            return $siswa;
        });

        return redirect()
            ->route('admin.siswa.show', $siswa)
            ->with('sukses', 'Data siswa berhasil ditambahkan dan QR Code aktif telah dibuat.');
    }

    public function show(Siswa $siswa): View
    {
        $siswa->load([
            'pengguna',
            'riwayatKelas.kelas.tahunAjaran',
            'kodeQrAktif',
            'permintaanKonfirmasi.sesiAbsensi.jadwal.mataPelajaran',
        ]);

        $kelasSaatIni = $siswa->kelasSaatIni();

        $semester = Semester::query()
            ->where('status_aktif', true)
            ->orderByDesc('tanggal_mulai')
            ->first();

        $dari = $semester?->tanggal_mulai?->copy() ?? now()->startOfMonth();
        $sampai = now()->startOfDay();

        if ($dari->gt($sampai)) {
            $dari = $sampai->copy()->startOfMonth();
        }

        $rekapMapel = $this->rekap->rekapMapel($siswa->id_siswa, $dari, $sampai);
        $rekapHarian = $this->rekap->rekapHarian($siswa->id_siswa, now()->subDays(6)->startOfDay(), $sampai);

        $catatanTerbaru = CatatanAbsensi::query()
            ->with(['sesiAbsensi.jadwal.mataPelajaran', 'sesiAbsensi.jadwal.kelas'])
            ->where('id_siswa', $siswa->id_siswa)
            ->orderByDesc('dibuat_pada')
            ->limit(10)
            ->get();

        $jumlahHadir = $rekapMapel->sum('hadir');
        $jumlahSesi = $rekapMapel->sum('total_pertemuan');
        $persentase = $jumlahSesi > 0 ? round(($jumlahHadir / $jumlahSesi) * 100, 1) : 0.0;

        $qrSvg = $siswa->kodeQrAktif ? $this->qr->gambarSvg($siswa->kodeQrAktif, 220) : null;

        return view('admin.siswa.show', compact(
            'siswa',
            'kelasSaatIni',
            'semester',
            'rekapMapel',
            'rekapHarian',
            'catatanTerbaru',
            'persentase',
            'dari',
            'sampai',
            'qrSvg',
        ));
    }

    public function edit(Siswa $siswa): View
    {
        $siswa->load(['pengguna', 'riwayatKelasAktif.kelas']);
        $kelas = $this->daftarKelas();
        $kelasSaatIni = $siswa->kelasSaatIni();

        return view('admin.siswa.edit', compact('siswa', 'kelas', 'kelasSaatIni'));
    }

    public function update(SiswaRequest $request, Siswa $siswa): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $request, $siswa) {
            $sebelum = $siswa->load('pengguna')->toArray();

            $penggunaData = [
                'nama_pengguna' => $data['nama_pengguna'],
                'email' => $data['email'],
                'status_aktif' => $data['status_aktif'],
            ];

            if (! empty($data['kata_sandi'])) {
                $penggunaData['kata_sandi'] = $data['kata_sandi'];
            }

            $siswa->pengguna->update($penggunaData);

            $siswa->update([
                'nis' => $data['nis'],
                'nisn' => $data['nisn'] ?? null,
                'nama_lengkap' => $data['nama_lengkap'],
                'jenis_kelamin' => $data['jenis_kelamin'],
                'tempat_lahir' => $data['tempat_lahir'] ?? null,
                'tanggal_lahir' => $data['tanggal_lahir'] ?? null,
                'agama' => $data['agama'] ?? null,
                'alamat' => $data['alamat'] ?? null,
                'nomor_telepon' => $data['nomor_telepon'] ?? null,
                'nama_orang_tua' => $data['nama_orang_tua'] ?? null,
                'nomor_telepon_orang_tua' => $data['nomor_telepon_orang_tua'] ?? null,
                'status_siswa' => $data['status_siswa'],
            ]);

            if (! empty($data['id_kelas'])) {
                $tanggal = isset($data['tanggal_mulai_kelas'])
                    ? Carbon::parse($data['tanggal_mulai_kelas'])
                    : now()->startOfDay();

                $this->pindahkanKelas($siswa, (int) $data['id_kelas'], $tanggal, $request->user());
            }

            $this->log->catat(
                $request->user(),
                'ubah_siswa',
                'Data Siswa',
                "Memperbarui data siswa {$siswa->nama_lengkap}.",
                'siswa',
                $siswa->id_siswa,
                $sebelum,
                $siswa->fresh('pengguna')->toArray()
            );
        });

        return redirect()
            ->route('admin.siswa.show', $siswa)
            ->with('sukses', 'Data siswa berhasil diperbarui.');
    }

    public function regenerasiQr(Request $request, Siswa $siswa): RedirectResponse
    {
        $this->qr->buat($siswa, $request->user());

        return redirect()
            ->route('admin.siswa.show', $siswa)
            ->with('sukses', 'QR Code baru berhasil dibuat. QR lama otomatis dicabut.');
    }

    public function cabutQr(Request $request, Siswa $siswa): RedirectResponse
    {
        $kode = $siswa->kodeQrAktif;

        if (! $kode instanceof KodeQrSiswa) {
            return redirect()
                ->route('admin.siswa.show', $siswa)
                ->with('error', 'Siswa ini tidak memiliki QR Code aktif.');
        }

        $this->qr->cabut($kode, $request->user());

        return redirect()
            ->route('admin.siswa.show', $siswa)
            ->with('sukses', 'QR Code siswa berhasil dicabut.');
    }

    protected function daftarKelas()
    {
        return Kelas::query()
            ->with('tahunAjaran')
            ->where('status_kelas', 'aktif')
            ->orderByDesc('id_tahun_ajaran')
            ->orderBy('nama_kelas')
            ->get();
    }

    protected function pindahkanKelas(Siswa $siswa, int $idKelas, Carbon $tanggal, Pengguna $admin): void
    {
        $aktif = $siswa->riwayatKelas()->where('status_aktif', true)->get();

        if ($aktif->contains(fn (RiwayatKelasSiswa $r) => (int) $r->id_kelas === $idKelas)) {
            return;
        }

        $kelas = Kelas::findOrFail($idKelas);
        $tanggal = $tanggal->copy()->startOfDay();
        $akhir = $tanggal->copy()->subDay();

        foreach ($aktif as $riwayat) {
            $penutup = $akhir->lt($riwayat->tanggal_mulai) ? $riwayat->tanggal_mulai->copy() : $akhir->copy();

            $riwayat->update([
                'status_aktif' => false,
                'tanggal_selesai' => $penutup->toDateString(),
            ]);
        }

        RiwayatKelasSiswa::updateOrCreate(
            [
                'id_siswa' => $siswa->id_siswa,
                'id_kelas' => $kelas->id_kelas,
                'id_tahun_ajaran' => $kelas->id_tahun_ajaran,
            ],
            [
                'tanggal_mulai' => $tanggal->toDateString(),
                'tanggal_selesai' => null,
                'status_aktif' => true,
            ]
        );

        $this->log->catat(
            $admin,
            'pindah_kelas',
            'Data Siswa',
            "Memindahkan {$siswa->nama_lengkap} ke kelas {$kelas->nama_kelas} per ".$tanggal->format('d/m/Y').'.',
            'siswa',
            $siswa->id_siswa
        );
    }
}
