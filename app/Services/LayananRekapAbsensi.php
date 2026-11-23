<?php

namespace App\Services;

use App\Models\CatatanAbsensi;
use App\Models\Kelas;
use App\Models\RingkasanAbsensiHarian;
use App\Models\RiwayatKelasSiswa;
use App\Models\SesiAbsensi;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class LayananRekapAbsensi
{
    public function __construct(private readonly LayananJadwal $jadwal)
    {
    }

    public function kelasPadaTanggal(int $idSiswa, Carbon $tanggal): ?Kelas
    {
        $tanggal = $tanggal->copy()->startOfDay();

        return \App\Models\RiwayatKelasSiswa::query()
            ->with('kelas')
            ->where('id_siswa', $idSiswa)
            ->whereDate('tanggal_mulai', '<=', $tanggal)
            ->where(function ($q) use ($tanggal) {
                $q->whereNull('tanggal_selesai')
                    ->orWhereDate('tanggal_selesai', '>=', $tanggal);
            })
            ->orderByDesc('tanggal_mulai')
            ->first()
            ?->kelas;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function rekapMapel(
        int $idSiswa,
        Carbon $dari,
        Carbon $sampai,
        ?int $idMataPelajaran = null,
        ?int $idSemester = null,
    ): Collection {
        $dari = $dari->copy()->startOfDay();
        $sampai = $sampai->copy()->endOfDay();

        $this->jadwal->pastikanSesiRentang($dari, $sampai);

        $sesi = SesiAbsensi::query()
            ->with(['jadwal.mataPelajaran', 'jadwal.versiJadwal'])
            ->whereBetween('tanggal_pelajaran', [$dari->toDateString(), $sampai->toDateString()])
            ->where('status_sesi', '!=', SesiAbsensi::STATUS_DIBATALKAN)
            ->where('waktu_selesai', '<=', now())
            ->when($idMataPelajaran, fn ($q) => $q->whereHas('jadwal', fn ($qq) => $qq->where('id_mata_pelajaran', $idMataPelajaran)))
            ->when($idSemester, fn ($q) => $q->whereHas('jadwal.versiJadwal', fn ($qq) => $qq->where('id_semester', $idSemester)))
            ->orderBy('tanggal_pelajaran')
            ->get();

        $kelasCache = [];

        $sesi = $sesi->filter(function (SesiAbsensi $item) use ($idSiswa, &$kelasCache) {
            $tanggal = $item->tanggal_pelajaran->toDateString();

            if (! array_key_exists($tanggal, $kelasCache)) {
                $kelasCache[$tanggal] = $this->kelasPadaTanggal($idSiswa, $item->tanggal_pelajaran)?->id_kelas;
            }

            return $kelasCache[$tanggal] === $item->jadwal->id_kelas;
        });

        $catatan = CatatanAbsensi::query()
            ->where('id_siswa', $idSiswa)
            ->whereIn('id_sesi_absensi', $sesi->pluck('id_sesi_absensi'))
            ->get()
            ->keyBy('id_sesi_absensi');

        $kelompok = [];

        foreach ($sesi as $item) {
            $mapel = $item->jadwal->mataPelajaran;

            if (! $mapel) {
                continue;
            }

            $id = $mapel->id_mata_pelajaran;

            if (! isset($kelompok[$id])) {
                $kelompok[$id] = [
                    'id_mata_pelajaran' => $id,
                    'nama_mata_pelajaran' => $mapel->nama_mata_pelajaran,
                    'total_pertemuan' => 0,
                    'hadir' => 0,
                    'izin' => 0,
                    'sakit' => 0,
                    'alpha' => 0,
                    'belum_diisi' => 0,
                    'persentase_hadir' => 0.0,
                ];
            }

            $kelompok[$id]['total_pertemuan']++;

            $status = $catatan->get($item->id_sesi_absensi)?->status_kehadiran;

            if ($status) {
                $kelompok[$id][$status]++;
            } else {
                $kelompok[$id]['belum_diisi']++;
            }
        }

        return collect($kelompok)
            ->map(function (array $baris) {
                $baris['persentase_hadir'] = $baris['total_pertemuan'] > 0
                    ? round(($baris['hadir'] / $baris['total_pertemuan']) * 100, 1)
                    : 0.0;

                return $baris;
            })
            ->sortBy('nama_mata_pelajaran')
            ->values();
    }

    /**
     * Rekap kehadiran banyak siswa sekaligus berdasarkan sumber data sesi dan
     * catatan absensi. Mengembalikan array dengan kunci `id_siswa`.
     *
     * @param  array<int, int>  $idsSiswa
     * @return Collection<int, array{total_pertemuan: int, hadir: int, izin: int, sakit: int, alpha: int, belum_diisi: int}>
     */
    public function rekapBanyakSiswa(array $idsSiswa, Carbon $dari, Carbon $sampai, ?int $idSemester = null): Collection
    {
        $idsSiswa = array_values(array_unique(array_filter(array_map('intval', $idsSiswa))));

        if ($idsSiswa === []) {
            return collect();
        }

        $dari = $dari->copy()->startOfDay();
        $sampai = $sampai->copy()->startOfDay();

        if ($sampai->gt(now()->startOfDay())) {
            $sampai = now()->copy()->startOfDay();
        }

        if ($dari->gt($sampai)) {
            return collect();
        }

        $sesi = SesiAbsensi::query()
            ->with('jadwal:id_jadwal,id_kelas')
            ->whereBetween('tanggal_pelajaran', [$dari->toDateString(), $sampai->toDateString()])
            ->where('status_sesi', '!=', SesiAbsensi::STATUS_DIBATALKAN)
            ->whereNotNull('waktu_selesai')
            ->where('waktu_selesai', '<=', now())
            ->when($idSemester, fn ($q) => $q->whereHas('jadwal.versiJadwal', fn ($qq) => $qq->where('id_semester', $idSemester)))
            ->orderBy('tanggal_pelajaran')
            ->get();

        $riwayat = RiwayatKelasSiswa::query()
            ->whereIn('id_siswa', $idsSiswa)
            ->orderBy('tanggal_mulai')
            ->get();

        $catatan = CatatanAbsensi::query()
            ->whereIn('id_siswa', $idsSiswa)
            ->whereIn('id_sesi_absensi', $sesi->pluck('id_sesi_absensi'))
            ->get(['id_siswa', 'id_sesi_absensi', 'status_kehadiran']);

        $statusCatatan = [];

        foreach ($catatan as $baris) {
            $statusCatatan[$baris->id_siswa.':'.$baris->id_sesi_absensi] = $baris->status_kehadiran;
        }

        $rekap = [];

        foreach ($idsSiswa as $idSiswa) {
            $rekap[$idSiswa] = [
                'total_pertemuan' => 0,
                'hadir' => 0,
                'izin' => 0,
                'sakit' => 0,
                'alpha' => 0,
                'belum_diisi' => 0,
            ];
        }

        foreach ($sesi->groupBy(fn (SesiAbsensi $sesi) => $sesi->jadwal?->id_kelas) as $idKelas => $sesiKelas) {
            if ($idKelas === null) {
                continue;
            }

            foreach ($riwayat->where('id_kelas', $idKelas) as $riwayatKelas) {
                $idSiswa = $riwayatKelas->id_siswa;

                if (! isset($rekap[$idSiswa])) {
                    continue;
                }

                $awal = $riwayatKelas->tanggal_mulai?->copy()->startOfDay()->toDateString();

                if ($awal === null) {
                    continue;
                }

                $akhir = $riwayatKelas->tanggal_selesai
                    ? $riwayatKelas->tanggal_selesai->copy()->endOfDay()->toDateString()
                    : $sampai->copy()->endOfDay()->toDateString();

                foreach ($sesiKelas as $item) {
                    $tanggal = $item->tanggal_pelajaran->toDateString();

                    if ($tanggal < $awal || $tanggal > $akhir) {
                        continue;
                    }

                    $rekap[$idSiswa]['total_pertemuan']++;

                    $status = $statusCatatan[$idSiswa.':'.$item->id_sesi_absensi] ?? null;

                    if ($status !== null && array_key_exists($status, $rekap[$idSiswa])) {
                        $rekap[$idSiswa][$status]++;
                    } else {
                        $rekap[$idSiswa]['belum_diisi']++;
                    }
                }
            }
        }

        return collect($rekap);
    }

    /**
     * @return Collection<int, RingkasanAbsensiHarian>
     */
    public function rekapHarian(int $idSiswa, Carbon $dari, Carbon $sampai, ?int $idSemester = null): Collection
    {
        $dari = $dari->copy()->startOfDay();
        $sampai = $sampai->copy()->startOfDay();
        $hariIni = now()->copy()->startOfDay();

        if ($sampai->gt($hariIni)) {
            $sampai = $hariIni->copy();
        }

        if ($dari->gt($sampai)) {
            return collect();
        }

        $this->jadwal->pastikanSesiRentang($dari, $sampai);

        for ($tanggal = $dari->copy(); $tanggal->lte($sampai); $tanggal->addDay()) {
            $kelas = $this->kelasPadaTanggal($idSiswa, $tanggal);

            if (! $kelas) {
                RingkasanAbsensiHarian::query()
                    ->where('id_siswa', $idSiswa)
                    ->whereDate('tanggal', $tanggal)
                    ->delete();

                continue;
            }

            $sesi = SesiAbsensi::query()
                ->whereDate('tanggal_pelajaran', $tanggal)
                ->where('status_sesi', '!=', SesiAbsensi::STATUS_DIBATALKAN)
                ->whereHas('jadwal', fn ($q) => $q->where('id_kelas', $kelas->id_kelas))
                ->when($idSemester, fn ($q) => $q->whereHas('jadwal.versiJadwal', fn ($qq) => $qq->where('id_semester', $idSemester)))
                ->orderBy('waktu_mulai')
                ->get();

            if ($sesi->isEmpty()) {
                RingkasanAbsensiHarian::query()
                    ->where('id_siswa', $idSiswa)
                    ->whereDate('tanggal', $tanggal)
                    ->delete();

                continue;
            }

            $catatan = CatatanAbsensi::query()
                ->where('id_siswa', $idSiswa)
                ->whereIn('id_sesi_absensi', $sesi->pluck('id_sesi_absensi'))
                ->get()
                ->keyBy('id_sesi_absensi');

            $jumlah = [
                'hadir' => 0,
                'izin' => 0,
                'sakit' => 0,
                'alpha' => 0,
            ];

            foreach ($sesi as $item) {
                $status = $catatan->get($item->id_sesi_absensi)?->status_kehadiran;

                if ($status) {
                    $jumlah[$status]++;
                }
            }

            $total = $sesi->count();
            $belum = $total - $catatan->count();
            $statusHarian = $this->kategoriHarian($total, $jumlah['hadir'], $jumlah['izin'], $jumlah['sakit'], $jumlah['alpha'], $belum);

            RingkasanAbsensiHarian::query()->updateOrCreate(
                ['id_siswa' => $idSiswa, 'tanggal' => $tanggal->toDateString()],
                [
                    'id_kelas' => $kelas->id_kelas,
                    'jumlah_mapel_dijadwalkan' => $total,
                    'jumlah_hadir' => $jumlah['hadir'],
                    'jumlah_izin' => $jumlah['izin'],
                    'jumlah_sakit' => $jumlah['sakit'],
                    'jumlah_alpha' => $jumlah['alpha'],
                    'jumlah_belum_diisi' => $belum,
                    'status_harian' => $statusHarian,
                ]
            );
        }

        return RingkasanAbsensiHarian::query()
            ->with('kelas')
            ->where('id_siswa', $idSiswa)
            ->whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])
            ->orderBy('tanggal')
            ->get();
    }

    public function hitungUlangHarian(int $idSiswa, Carbon $tanggal): void
    {
        $this->rekapHarian($idSiswa, $tanggal->copy()->startOfDay(), $tanggal->copy()->startOfDay());
    }

    public function kategoriHarian(int $total, int $hadir, int $izin, int $sakit, int $alpha, int $belum): string
    {
        if ($belum > 0) {
            return 'belum_lengkap';
        }

        if ($total > 0 && $hadir === $total) {
            return 'hadir_penuh';
        }

        if ($total > 0 && $izin === $total) {
            return 'izin_penuh';
        }

        if ($total > 0 && $sakit === $total) {
            return 'sakit_penuh';
        }

        if ($total > 0 && $alpha === $total) {
            return 'alpha_penuh';
        }

        if ($total > 0 && $hadir > 0) {
            return 'parsial';
        }

        return 'campuran_non_hadir';
    }

    /**
     * Ringkasan agregat seluruh siswa pada satu kelas untuk laporan admin.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function ringkasanKelas(int $idKelas, Carbon $dari, Carbon $sampai, ?int $idMataPelajaran = null): Collection
    {
        $dari = $dari->copy()->startOfDay();
        $sampai = $sampai->copy()->endOfDay();

        $this->jadwal->pastikanSesiRentang($dari, $sampai);

        $sesi = SesiAbsensi::query()
            ->whereBetween('tanggal_pelajaran', [$dari->toDateString(), $sampai->toDateString()])
            ->where('status_sesi', '!=', SesiAbsensi::STATUS_DIBATALKAN)
            ->where('waktu_selesai', '<=', now())
            ->whereHas('jadwal', function ($q) use ($idKelas, $idMataPelajaran) {
                $q->where('id_kelas', $idKelas);

                if ($idMataPelajaran) {
                    $q->where('id_mata_pelajaran', $idMataPelajaran);
                }
            })
            ->pluck('id_sesi_absensi');

        $catatan = CatatanAbsensi::query()
            ->whereIn('id_sesi_absensi', $sesi)
            ->get()
            ->groupBy('id_siswa');

        $siswa = Siswa::query()
            ->with('pengguna')
            ->whereHas('riwayatKelas', function ($q) use ($idKelas) {
                $q->where('id_kelas', $idKelas)->where('status_aktif', true);
            })
            ->orderBy('nama_lengkap')
            ->get();

        $totalSesi = $sesi->count();

        return $siswa->map(function (Siswa $murid) use ($catatan, $totalSesi) {
            $data = $catatan->get($murid->id_siswa, collect());
            $hadir = $data->where('status_kehadiran', CatatanAbsensi::HADIR)->count();
            $izin = $data->where('status_kehadiran', CatatanAbsensi::IZIN)->count();
            $sakit = $data->where('status_kehadiran', CatatanAbsensi::SAKIT)->count();
            $alpha = $data->where('status_kehadiran', CatatanAbsensi::ALPHA)->count();
            $belum = max(0, $totalSesi - $data->count());

            return [
                'id_siswa' => $murid->id_siswa,
                'nis' => $murid->nis,
                'nama_lengkap' => $murid->nama_lengkap,
                'total_pertemuan' => $totalSesi,
                'hadir' => $hadir,
                'izin' => $izin,
                'sakit' => $sakit,
                'alpha' => $alpha,
                'belum_diisi' => $belum,
                'persentase_hadir' => $totalSesi > 0 ? round(($hadir / $totalSesi) * 100, 1) : 0.0,
            ];
        });
    }
}
