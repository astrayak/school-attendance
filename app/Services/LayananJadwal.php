<?php

namespace App\Services;

use App\Models\Guru;
use App\Models\JadwalPelajaran;
use App\Models\Pengguna;
use App\Models\PenugasanGuru;
use App\Models\Semester;
use App\Models\SesiAbsensi;
use App\Models\VersiJadwal;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LayananJadwal
{
    public function __construct(
        private readonly LayananPengaturan $pengaturan,
        private readonly LayananPeriode $periode,
    ) {
    }

    public static function namaHari(Carbon $tanggal): string
    {
        return strtolower($tanggal->copy()->locale('id')->translatedFormat('l'));
    }

    public function versiBerlakuPada(Carbon $tanggal, bool $hanyaSemesterAktif = false): ?VersiJadwal
    {
        $tanggal = $tanggal->copy()->startOfDay();

        return VersiJadwal::query()
            ->with('semester')
            ->whereIn('status_versi', [VersiJadwal::STATUS_AKTIF, VersiJadwal::STATUS_ARSIP])
            ->whereDate('tanggal_berlaku', '<=', $tanggal)
            ->where(function ($q) use ($tanggal) {
                $q->whereNull('tanggal_berakhir')
                    ->orWhereDate('tanggal_berakhir', '>=', $tanggal);
            })
            ->whereHas('semester', function ($q) use ($tanggal, $hanyaSemesterAktif) {
                $q->whereDate('tanggal_mulai', '<=', $tanggal)
                    ->whereDate('tanggal_selesai', '>=', $tanggal);

                if ($hanyaSemesterAktif) {
                    $q->where('status_aktif', true);
                }
            })
            ->orderByRaw("CASE WHEN status_versi = 'aktif' THEN 0 ELSE 1 END")
            ->orderByDesc('tanggal_berlaku')
            ->first();
    }

    public function versiAktifSekarang(): ?VersiJadwal
    {
        $semester = $this->periode->aktif();

        return $semester ? $this->versiUntukSemester($semester, now()) : null;
    }

    public function versiUntukSemester(Semester $semester, Carbon $tanggal): ?VersiJadwal
    {
        $tanggal = $tanggal->copy()->startOfDay();

        return VersiJadwal::query()
            ->with('semester')
            ->where('id_semester', $semester->id_semester)
            ->whereIn('status_versi', [VersiJadwal::STATUS_AKTIF, VersiJadwal::STATUS_ARSIP])
            ->whereDate('tanggal_berlaku', '<=', $tanggal)
            ->where(function ($q) use ($tanggal) {
                $q->whereNull('tanggal_berakhir')
                    ->orWhereDate('tanggal_berakhir', '>=', $tanggal);
            })
            ->orderByRaw("CASE WHEN status_versi = 'aktif' THEN 0 ELSE 1 END")
            ->orderByDesc('tanggal_berlaku')
            ->first();
    }

    /**
     * Jadwal guru pada versi/periode tertentu. Hanya jadwal yang masih memiliki
     * penugasan guru aktif pada semester dan tanggal tersebut yang ditampilkan.
     *
     * @return Collection<int, JadwalPelajaran>
     */
    public function jadwalGuru(VersiJadwal $versi, int $idGuru, Carbon $tanggal): Collection
    {
        $tanggal = $tanggal->copy()->startOfDay();

        return JadwalPelajaran::query()
            ->with(['kelas', 'mataPelajaran'])
            ->where('id_versi_jadwal', $versi->id_versi_jadwal)
            ->where('id_guru', $idGuru)
            ->where('status_jadwal', 'aktif')
            ->whereExists(function ($query) use ($versi, $tanggal) {
                $query->select(DB::raw(1))
                    ->from('penugasan_guru')
                    ->whereColumn('penugasan_guru.id_guru', 'jadwal_pelajaran.id_guru')
                    ->whereColumn('penugasan_guru.id_mata_pelajaran', 'jadwal_pelajaran.id_mata_pelajaran')
                    ->whereColumn('penugasan_guru.id_kelas', 'jadwal_pelajaran.id_kelas')
                    ->where('penugasan_guru.id_semester', $versi->id_semester)
                    ->where('penugasan_guru.status_aktif', true)
                    ->whereDate('penugasan_guru.tanggal_mulai', '<=', $tanggal->toDateString())
                    ->where(function ($q) use ($tanggal) {
                        $q->whereNull('penugasan_guru.tanggal_selesai')
                            ->orWhereDate('penugasan_guru.tanggal_selesai', '>=', $tanggal->toDateString());
                    });
            })
            ->orderBy('jam_mulai')
            ->get();
    }

    public function pastikanSesiTanggal(Carbon $tanggal): Collection
    {
        $tanggal = $tanggal->copy()->startOfDay();
        $semesterAktif = $this->periode->aktif();
        $wajibSemesterAktif = $semesterAktif !== null && $this->periode->mencakup($semesterAktif, $tanggal);
        $versi = $wajibSemesterAktif
            ? $this->versiUntukSemester($semesterAktif, $tanggal)
            : $this->versiBerlakuPada($tanggal);

        if (! $versi) {
            return collect();
        }

        $hari = self::namaHari($tanggal);
        $pengaturan = $this->pengaturan->semua($tanggal);

        $daftarJadwal = JadwalPelajaran::query()
            ->with(['kelas', 'mataPelajaran', 'guru'])
            ->where('id_versi_jadwal', $versi->id_versi_jadwal)
            ->where('hari', $hari)
            ->where('status_jadwal', 'aktif')
            ->orderBy('jam_mulai')
            ->get();

        foreach ($daftarJadwal as $jadwal) {
            $mulai = Carbon::parse($tanggal->toDateString().' '.$jadwal->jam_mulai);
            $selesai = Carbon::parse($tanggal->toDateString().' '.$jadwal->jam_selesai);
            $dibuka = $selesai->copy()->subMinutes((int) $pengaturan['menit_buka_sebelum_selesai']);
            $ditutup = $selesai->copy()->addMinutes((int) $pengaturan['menit_tutup_setelah_selesai']);
            $batasKonfirmasi = $selesai->copy()->addMinutes((int) $pengaturan['menit_batas_konfirmasi']);

            $statusAwal = match (true) {
                now()->lt($dibuka) => SesiAbsensi::STATUS_MENUNGGU,
                now()->lte($ditutup) => SesiAbsensi::STATUS_AKTIF,
                default => SesiAbsensi::STATUS_DITUTUP,
            };

            SesiAbsensi::query()->firstOrCreate(
                [
                    'id_jadwal' => $jadwal->id_jadwal,
                    'tanggal_pelajaran' => $tanggal->toDateString(),
                ],
                [
                    'waktu_mulai' => $mulai,
                    'waktu_selesai' => $selesai,
                    'waktu_absensi_dibuka' => $dibuka,
                    'waktu_absensi_ditutup' => $ditutup,
                    'waktu_batas_konfirmasi' => $batasKonfirmasi,
                    'id_guru_penanggung_jawab' => $jadwal->id_guru,
                    'status_sesi' => $statusAwal,
                ]
            );
        }

        return SesiAbsensi::query()
            ->with(['jadwal.kelas', 'jadwal.mataPelajaran', 'jadwal.versiJadwal.semester', 'guruPenanggungJawab', 'guruPelaksana'])
            ->whereDate('tanggal_pelajaran', $tanggal)
            ->whereHas('jadwal', fn ($q) => $q->where('id_versi_jadwal', $versi->id_versi_jadwal))
            ->orderBy('waktu_mulai')
            ->get();
    }

    public function pastikanSesiRentang(Carbon $mulai, Carbon $selesai): void
    {
        $mulai = $mulai->copy()->startOfDay();
        $selesai = $selesai->copy()->startOfDay();

        if ($mulai->gt($selesai)) {
            [$mulai, $selesai] = [$selesai, $mulai];
        }

        if ($mulai->diffInDays($selesai) > 366) {
            throw ValidationException::withMessages([
                'tanggal' => 'Rentang rekap maksimal 366 hari agar proses tetap ringan.',
            ]);
        }

        for ($tanggal = $mulai->copy(); $tanggal->lte($selesai); $tanggal->addDay()) {
            $this->pastikanSesiTanggal($tanggal);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, string>
     */
    public function validasiBenturan(array $data, ?int $abaikanIdJadwal = null): array
    {
        $errors = [];
        $mulai = Carbon::parse((string) $data['jam_mulai'])->format('H:i:s');
        $selesai = Carbon::parse((string) $data['jam_selesai'])->format('H:i:s');

        if ($mulai >= $selesai) {
            return ['Jam selesai harus lebih besar daripada jam mulai.'];
        }

        $dasar = JadwalPelajaran::query()
            ->with(['kelas', 'guru', 'mataPelajaran'])
            ->where('id_versi_jadwal', $data['id_versi_jadwal'])
            ->where('hari', $data['hari'])
            ->where('status_jadwal', 'aktif')
            ->where('jam_mulai', '<', $selesai)
            ->where('jam_selesai', '>', $mulai);

        if ($abaikanIdJadwal) {
            $dasar->where('id_jadwal', '!=', $abaikanIdJadwal);
        }

        $benturanKelas = (clone $dasar)->where('id_kelas', $data['id_kelas'])->first();

        if ($benturanKelas) {
            $errors[] = "Kelas {$benturanKelas->kelas?->nama_kelas} sudah memiliki jadwal "
                .substr($benturanKelas->jam_mulai, 0, 5).'-'.substr($benturanKelas->jam_selesai, 0, 5)
                ." ({$benturanKelas->mataPelajaran?->nama_mata_pelajaran}).";
        }

        $benturanGuru = (clone $dasar)->where('id_guru', $data['id_guru'])->first();

        if ($benturanGuru) {
            $errors[] = "Guru {$benturanGuru->guru?->nama_lengkap} sudah mengajar di "
                .$benturanGuru->kelas?->nama_kelas.' pukul '
                .substr($benturanGuru->jam_mulai, 0, 5).'-'.substr($benturanGuru->jam_selesai, 0, 5).'.';
        }

        return $errors;
    }

    public function guruDitugaskan(
        int $idGuru,
        int $idMataPelajaran,
        int $idKelas,
        int $idSemester,
        Carbon $tanggal,
    ): bool {
        return PenugasanGuru::query()
            ->where('id_guru', $idGuru)
            ->where('id_mata_pelajaran', $idMataPelajaran)
            ->where('id_kelas', $idKelas)
            ->where('id_semester', $idSemester)
            ->where('status_aktif', true)
            ->whereDate('tanggal_mulai', '<=', $tanggal)
            ->where(function ($q) use ($tanggal) {
                $q->whereNull('tanggal_selesai')
                    ->orWhereDate('tanggal_selesai', '>=', $tanggal);
            })
            ->exists();
    }

    public function versiBolehDiubah(VersiJadwal $versi): bool
    {
        return ! SesiAbsensi::query()
            ->whereHas('jadwal', fn ($q) => $q->where('id_versi_jadwal', $versi->id_versi_jadwal))
            ->exists();
    }

    public function buatRevisi(VersiJadwal $lama, Pengguna $admin, string $namaVersi, Carbon $tanggalBerlaku): VersiJadwal
    {
        return DB::transaction(function () use ($lama, $admin, $namaVersi, $tanggalBerlaku) {
            $baru = VersiJadwal::create([
                'id_semester' => $lama->id_semester,
                'nama_versi' => $namaVersi,
                'tanggal_berlaku' => $tanggalBerlaku->toDateString(),
                'status_versi' => VersiJadwal::STATUS_DRAF,
                'dibuat_oleh' => $admin->id_pengguna,
            ]);

            $lama->load('jadwal');

            foreach ($lama->jadwal as $jadwal) {
                JadwalPelajaran::create([
                    'id_versi_jadwal' => $baru->id_versi_jadwal,
                    'id_kelas' => $jadwal->id_kelas,
                    'id_mata_pelajaran' => $jadwal->id_mata_pelajaran,
                    'id_guru' => $jadwal->id_guru,
                    'hari' => $jadwal->hari,
                    'jam_mulai' => $jadwal->jam_mulai,
                    'jam_selesai' => $jadwal->jam_selesai,
                    'ruang_kelas' => $jadwal->ruang_kelas,
                    'urutan_pelajaran' => $jadwal->urutan_pelajaran,
                    'status_jadwal' => $jadwal->status_jadwal,
                ]);
            }

            return $baru;
        });
    }

    public function aktifkanVersi(VersiJadwal $versi, Pengguna $admin): void
    {
        $semesterAktif = $this->periode->aktif();
        $boleh = $versi->semester?->status_aktif
            || ($semesterAktif && $versi->id_semester === $semesterAktif->id_semester);

        if (! $boleh) {
            throw ValidationException::withMessages([
                'versi' => 'Semester versi jadwal ini tidak sedang aktif. Aktifkan semester yang sesuai terlebih dahulu.',
            ]);
        }

        if (! $versi->jadwal()->exists()) {
            throw ValidationException::withMessages([
                'versi' => 'Versi jadwal belum memiliki jadwal pelajaran. Tambahkan minimal satu jadwal terlebih dahulu.',
            ]);
        }

        DB::transaction(function () use ($versi) {
            $lain = VersiJadwal::query()
                ->where('id_semester', $versi->id_semester)
                ->where('id_versi_jadwal', '!=', $versi->id_versi_jadwal)
                ->whereIn('status_versi', [VersiJadwal::STATUS_AKTIF, VersiJadwal::STATUS_ARSIP])
                ->get();

            foreach ($lain as $item) {
                $akhir = $versi->tanggal_berlaku->copy()->subDay()->toDateString();

                if (! $item->tanggal_berakhir || $item->tanggal_berakhir->gte($versi->tanggal_berlaku)) {
                    $item->update([
                        'status_versi' => VersiJadwal::STATUS_ARSIP,
                        'tanggal_berakhir' => $akhir,
                    ]);
                }
            }

            $versi->update([
                'status_versi' => VersiJadwal::STATUS_AKTIF,
                'tanggal_berakhir' => $versi->tanggal_berakhir?->toDateString()
                    ?? $versi->semester?->tanggal_selesai?->toDateString(),
            ]);
        });
    }
}
