<?php

namespace App\Services;

use App\Models\Pengguna;
use App\Models\PermintaanKonfirmasi;
use App\Models\PetugasKonfirmasi;
use App\Models\SesiAbsensi;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LayananKonfirmasi
{
    public function __construct(
        private readonly LayananPengaturan $pengaturan,
        private readonly LayananNotifikasi $notifikasi,
        private readonly LayananLogAktivitas $log,
    ) {}

    public function batas(SesiAbsensi $sesi): Carbon
    {
        return $sesi->waktu_batas_konfirmasi?->copy()
            ?? $sesi->waktu_selesai->copy()->addMinutes((int) $this->pengaturan->nilai('menit_batas_konfirmasi'));
    }

    public function mulai(SesiAbsensi $sesi): void
    {
        $sesi->loadMissing(['jadwal.kelas', 'jadwal.mataPelajaran', 'jadwal.versiJadwal.semester']);

        if ($sesi->permintaanKonfirmasi()->exists()) {
            return;
        }

        if (now()->gte($this->batas($sesi))) {
            $this->gagalTanpaPermintaan($sesi);

            return;
        }

        $petugas = $this->petugasUntukSesi($sesi);
        $pertama = $petugas->first();

        if (! $pertama) {
            $this->notifikasi->kirimKeAdmin(
                'Belum ada petugas konfirmasi',
                "Sesi {$sesi->jadwal?->mataPelajaran?->nama_mata_pelajaran} kelas {$sesi->jadwal?->kelas?->nama_kelas} disimpan, tetapi belum ada petugas konfirmasi aktif. Tetapkan petugas melalui menu Petugas Konfirmasi.",
                'konfirmasi',
                route('admin.petugas-konfirmasi.index')
            );

            return;
        }

        $this->kirimPermintaan($sesi, $pertama);
    }

    public function jawab(
        PermintaanKonfirmasi $permintaan,
        string $jawaban,
        Pengguna $pengguna,
        ?string $keterangan = null,
    ): PermintaanKonfirmasi {
        $siswa = $pengguna->siswa;

        if (! $siswa || $permintaan->id_siswa_penerima !== $siswa->id_siswa) {
            throw ValidationException::withMessages([
                'konfirmasi' => 'Permintaan ini bukan untuk akun Anda.',
            ]);
        }

        if (! in_array($jawaban, [PermintaanKonfirmasi::BERLANGSUNG, PermintaanKonfirmasi::TIDAK_BERLANGSUNG], true)) {
            throw ValidationException::withMessages(['jawaban' => 'Pilihan jawaban tidak valid.']);
        }

        return DB::transaction(function () use ($permintaan, $jawaban, $pengguna, $keterangan) {
            $permintaan = PermintaanKonfirmasi::query()
                ->with(['sesiAbsensi.jadwal.kelas', 'sesiAbsensi.jadwal.mataPelajaran', 'sesiAbsensi.guruPelaksana.pengguna', 'sesiAbsensi.guruPenanggungJawab.pengguna'])
                ->lockForUpdate()
                ->findOrFail($permintaan->id_permintaan_konfirmasi);

            $sesi = SesiAbsensi::query()->lockForUpdate()->findOrFail($permintaan->id_sesi_absensi);

            if ($permintaan->status_permintaan !== PermintaanKonfirmasi::MENUNGGU) {
                throw ValidationException::withMessages([
                    'konfirmasi' => 'Permintaan konfirmasi ini sudah tidak aktif.',
                ]);
            }

            if ($sesi->status_sesi !== SesiAbsensi::STATUS_MENUNGGU_KONFIRMASI) {
                throw ValidationException::withMessages([
                    'konfirmasi' => 'Sesi ini sudah memiliki hasil konfirmasi final.',
                ]);
            }

            if (now()->lt($sesi->waktu_selesai)) {
                throw ValidationException::withMessages([
                    'konfirmasi' => 'Konfirmasi baru dapat dilakukan setelah pembelajaran berakhir pukul '
                        .$sesi->waktu_selesai->format('H:i').'.',
                ]);
            }

            if (now()->gt($permintaan->waktu_kedaluwarsa) || now()->gt($this->batas($sesi))) {
                $permintaan->update([
                    'status_permintaan' => PermintaanKonfirmasi::KEDALUWARSA,
                    'keterangan' => 'Waktu respons telah habis.',
                ]);

                throw ValidationException::withMessages([
                    'konfirmasi' => 'Waktu konfirmasi telah habis.',
                ]);
            }

            $permintaan->update([
                'status_permintaan' => PermintaanKonfirmasi::DIKONFIRMASI,
                'jawaban' => $jawaban,
                'waktu_direspons' => now(),
                'keterangan' => $keterangan,
            ]);

            if ($jawaban === PermintaanKonfirmasi::BERLANGSUNG) {
                $sesi->update(['status_sesi' => SesiAbsensi::STATUS_TERKONFIRMASI]);

                PermintaanKonfirmasi::query()
                    ->where('id_sesi_absensi', $sesi->id_sesi_absensi)
                    ->where('id_permintaan_konfirmasi', '!=', $permintaan->id_permintaan_konfirmasi)
                    ->where('status_permintaan', PermintaanKonfirmasi::MENUNGGU)
                    ->update([
                        'status_permintaan' => PermintaanKonfirmasi::DIALIHKAN,
                        'keterangan' => 'Sudah dikonfirmasi oleh petugas lain.',
                        'diperbarui_pada' => now(),
                    ]);
            } else {
                $sesi->update(['status_sesi' => SesiAbsensi::STATUS_PERLU_PEMERIKSAAN]);

                $this->notifikasi->kirimKeAdmin(
                    'Pemeriksaan pembelajaran diperlukan',
                    "Siswa {$pengguna->nama_pengguna} melaporkan pembelajaran TIDAK berlangsung untuk "
                        .($sesi->jadwal?->mataPelajaran?->nama_mata_pelajaran ?? 'mata pelajaran')
                        .'. Periksa bukti dan catatan absensi sebelum mengambil keputusan.',
                    'pemeriksaan',
                    route('admin.sesi.show', $sesi->id_sesi_absensi)
                );
            }

            $this->log->catat(
                $pengguna,
                'konfirmasi',
                'Konfirmasi Pembelajaran',
                'Memberikan konfirmasi: '.($jawaban === PermintaanKonfirmasi::BERLANGSUNG
                    ? 'pembelajaran berlangsung'
                    : 'pembelajaran tidak berlangsung').'.',
                'permintaan_konfirmasi',
                $permintaan->id_permintaan_konfirmasi,
                ['status_permintaan' => PermintaanKonfirmasi::MENUNGGU],
                ['status_permintaan' => PermintaanKonfirmasi::DIKONFIRMASI, 'jawaban' => $jawaban]
            );

            $guru = $sesi->guruPelaksana?->pengguna ?? $sesi->guruPenanggungJawab?->pengguna;

            if ($guru) {
                $this->notifikasi->kirim(
                    $guru,
                    $jawaban === PermintaanKonfirmasi::BERLANGSUNG
                        ? 'Pembelajaran dikonfirmasi'
                        : 'Laporan pembelajaran tidak berlangsung',
                    "Siswa {$pengguna->nama_pengguna} memberikan konfirmasi untuk sesi "
                        .($sesi->jadwal?->mataPelajaran?->nama_mata_pelajaran ?? 'mata pelajaran').'.',
                    'konfirmasi',
                    route('guru.sesi.show', $sesi->id_sesi_absensi)
                );
            }

            return $permintaan->fresh();
        });
    }

    public function prosesKedaluwarsa(): void
    {
        PermintaanKonfirmasi::query()
            ->where('status_permintaan', PermintaanKonfirmasi::MENUNGGU)
            ->where('waktu_kedaluwarsa', '<=', now())
            ->orderBy('id_permintaan_konfirmasi')
            ->chunkById(100, function (Collection $daftar) {
                foreach ($daftar as $permintaan) {
                    $this->prosesSatu($permintaan->id_permintaan_konfirmasi);
                }
            });

        SesiAbsensi::query()
            ->where('status_sesi', SesiAbsensi::STATUS_MENUNGGU_KONFIRMASI)
            ->where('waktu_batas_konfirmasi', '<=', now())
            ->whereDoesntHave('permintaanKonfirmasi', function ($q) {
                $q->where('status_permintaan', PermintaanKonfirmasi::MENUNGGU);
            })
            ->chunkById(100, function (Collection $daftar) {
                foreach ($daftar as $sesi) {
                    $this->gagalTanpaPermintaan($sesi);
                }
            });
    }

    public function daftarUntukSiswa(Siswa $siswa): Collection
    {
        $this->prosesKedaluwarsa();

        return PermintaanKonfirmasi::query()
            ->with([
                'sesiAbsensi.jadwal.kelas',
                'sesiAbsensi.jadwal.mataPelajaran',
                'sesiAbsensi.jadwal.versiJadwal.semester',
                'sesiAbsensi.guruPelaksana',
                'sesiAbsensi.guruPenanggungJawab',
            ])
            ->where('id_siswa_penerima', $siswa->id_siswa)
            ->where(function ($q) {
                $q->where('status_permintaan', PermintaanKonfirmasi::MENUNGGU)
                    ->orWhereDate('dibuat_pada', '>=', now()->subDays(7)->toDateString());
            })
            ->orderByRaw("CASE WHEN status_permintaan = 'menunggu' THEN 0 ELSE 1 END")
            ->orderByDesc('waktu_dikirim')
            ->limit(30)
            ->get();
    }

    public function jumlahMenunggu(Siswa $siswa): int
    {
        return PermintaanKonfirmasi::query()
            ->where('id_siswa_penerima', $siswa->id_siswa)
            ->where('status_permintaan', PermintaanKonfirmasi::MENUNGGU)
            ->count();
    }

    public function prosesSatu(int $idPermintaan): void
    {
        DB::transaction(function () use ($idPermintaan) {
            $permintaan = PermintaanKonfirmasi::query()
                ->with(['sesiAbsensi.jadwal.kelas', 'sesiAbsensi.jadwal.mataPelajaran', 'petugasKonfirmasi'])
                ->lockForUpdate()
                ->find($idPermintaan);

            if (! $permintaan || $permintaan->status_permintaan !== PermintaanKonfirmasi::MENUNGGU) {
                return;
            }

            $sesi = SesiAbsensi::query()->lockForUpdate()->find($permintaan->id_sesi_absensi);

            if (! $sesi) {
                return;
            }

            if ($sesi->status_sesi !== SesiAbsensi::STATUS_MENUNGGU_KONFIRMASI) {
                $permintaan->update([
                    'status_permintaan' => PermintaanKonfirmasi::DIALIHKAN,
                    'keterangan' => 'Sesi telah memiliki hasil konfirmasi final.',
                ]);

                return;
            }

            if (now()->lt($permintaan->waktu_kedaluwarsa)) {
                return;
            }

            $batas = $this->batas($sesi);

            if (now()->gte($batas)) {
                $this->gagalDenganPermintaan($permintaan, $sesi);

                return;
            }

            $permintaan->update([
                'status_permintaan' => PermintaanKonfirmasi::KEDALUWARSA,
                'keterangan' => 'Tidak ada respons sebelum batas waktu pengalihan.',
            ]);

            if (! (bool) $this->pengaturan->nilai('pengalihan_konfirmasi_otomatis', true)) {
                return;
            }

            $berikut = $this->petugasBerikut($sesi, $permintaan);

            if (! $berikut) {
                $this->gagalDenganPermintaan($permintaan->fresh(), $sesi);

                return;
            }

            $this->kirimPermintaan($sesi, $berikut, $batas);
        });
    }

    /**
     * @return Collection<int, PetugasKonfirmasi>
     */
    protected function petugasUntukSesi(SesiAbsensi $sesi): Collection
    {
        $tanggal = $sesi->tanggal_pelajaran;
        $kelas = $sesi->jadwal->id_kelas;
        $semester = $sesi->jadwal->versiJadwal->id_semester;

        return PetugasKonfirmasi::query()
            ->with('siswa')
            ->where('id_kelas', $kelas)
            ->where('id_semester', $semester)
            ->where('status_aktif', true)
            ->whereDate('tanggal_mulai', '<=', $tanggal)
            ->where(function ($q) use ($tanggal) {
                $q->whereNull('tanggal_selesai')
                    ->orWhereDate('tanggal_selesai', '>=', $tanggal);
            })
            ->whereHas('siswa', fn ($q) => $q->where('status_siswa', 'aktif'))
            ->orderBy('urutan_prioritas')
            ->get();
    }

    protected function petugasBerikut(SesiAbsensi $sesi, PermintaanKonfirmasi $permintaan): ?PetugasKonfirmasi
    {
        $prioritasSekarang = $permintaan->petugasKonfirmasi?->urutan_prioritas ?? 0;

        $sudahDipakai = PermintaanKonfirmasi::query()
            ->where('id_sesi_absensi', $sesi->id_sesi_absensi)
            ->pluck('id_siswa_penerima')
            ->all();

        return $this->petugasUntukSesi($sesi)
            ->where('urutan_prioritas', '>', $prioritasSekarang)
            ->whereNotIn('id_siswa', $sudahDipakai)
            ->first();
    }

    protected function kirimPermintaan(SesiAbsensi $sesi, PetugasKonfirmasi $petugas, ?Carbon $batas = null): PermintaanKonfirmasi
    {
        $batas ??= $this->batas($sesi);
        $interval = (int) $this->pengaturan->nilai('detik_per_alihan_konfirmasi', 60);

        // Permintaan tidak boleh kedaluwarsa sebelum pembelajaran berakhir,
        // karena siswa baru boleh menjawab setelah jam pelajaran selesai.
        $acuan = now()->gt($sesi->waktu_selesai) ? now()->copy() : $sesi->waktu_selesai->copy();
        $kedaluwarsa = $acuan->addSeconds($interval);

        if ($kedaluwarsa->gt($batas)) {
            $kedaluwarsa = $batas->copy();
        }

        if ($kedaluwarsa->lte(now())) {
            $this->gagalTanpaPermintaan($sesi);

            return new PermintaanKonfirmasi;
        }

        $urutan = ((int) PermintaanKonfirmasi::query()
            ->where('id_sesi_absensi', $sesi->id_sesi_absensi)
            ->max('urutan_penerima')) + 1;

        $permintaan = PermintaanKonfirmasi::create([
            'id_sesi_absensi' => $sesi->id_sesi_absensi,
            'id_siswa_penerima' => $petugas->id_siswa,
            'id_petugas_konfirmasi' => $petugas->id_petugas_konfirmasi,
            'urutan_penerima' => $urutan,
            'status_permintaan' => PermintaanKonfirmasi::MENUNGGU,
            'waktu_dikirim' => now(),
            'waktu_kedaluwarsa' => $kedaluwarsa,
        ]);

        $this->notifikasi->kirim(
            $petugas->siswa->pengguna,
            'Permintaan konfirmasi pembelajaran',
            'Konfirmasi apakah pembelajaran '.($sesi->jadwal?->mataPelajaran?->nama_mata_pelajaran ?? '')
                .' kelas '.($sesi->jadwal?->kelas?->nama_kelas ?? '').' benar-benar berlangsung. '
                .'Batas respons '.$kedaluwarsa->format('H:i:s').'.',
            'konfirmasi',
            route('siswa.konfirmasi.index')
        );

        return $permintaan;
    }

    protected function gagalTanpaPermintaan(SesiAbsensi $sesi): void
    {
        $sesi = SesiAbsensi::query()->lockForUpdate()->find($sesi->id_sesi_absensi);

        if (! $sesi || $sesi->status_sesi !== SesiAbsensi::STATUS_MENUNGGU_KONFIRMASI) {
            return;
        }

        $sesi->update(['status_sesi' => SesiAbsensi::STATUS_BELUM_DIKONFIRMASI]);

        $this->notifikasi->kirimKeAdmin(
            'Sesi belum dikonfirmasi',
            "Sesi {$sesi->jadwal?->mataPelajaran?->nama_mata_pelajaran} tanggal "
                .$sesi->tanggal_pelajaran->format('d/m/Y').' tidak mendapatkan konfirmasi siswa sampai batas waktu.',
            'konfirmasi',
            route('admin.sesi.show', $sesi->id_sesi_absensi)
        );
    }

    protected function gagalDenganPermintaan(PermintaanKonfirmasi $permintaan, SesiAbsensi $sesi): void
    {
        $permintaan->update([
            'status_permintaan' => PermintaanKonfirmasi::TIDAK_DIKONFIRMASI,
            'keterangan' => 'Tidak ada respons valid sampai batas waktu.',
        ]);

        $sesi->update(['status_sesi' => SesiAbsensi::STATUS_BELUM_DIKONFIRMASI]);

        $this->notifikasi->kirimKeAdmin(
            'Sesi belum dikonfirmasi',
            "Sesi {$sesi->jadwal?->mataPelajaran?->nama_mata_pelajaran} tanggal "
                .$sesi->tanggal_pelajaran->format('d/m/Y').' tidak dikonfirmasi oleh petugas mana pun.',
            'konfirmasi',
            route('admin.sesi.show', $sesi->id_sesi_absensi)
        );
    }
}
