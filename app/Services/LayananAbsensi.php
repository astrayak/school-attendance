<?php

namespace App\Services;

use App\Models\AuditAbsensi;
use App\Models\CatatanAbsensi;
use App\Models\Guru;
use App\Models\Pengguna;
use App\Models\PenugasanGuru;
use App\Models\SesiAbsensi;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LayananAbsensi
{
    public function __construct(
        private readonly LayananLogAktivitas $log,
        private readonly LayananKonfirmasi $konfirmasi,
        private readonly LayananRekapAbsensi $rekap,
        private readonly LayananGuruPiket $piket,
        private readonly LayananQrCode $qr,
    ) {}

    public function siswaPadaSesi(SesiAbsensi $sesi): Collection
    {
        $sesi->loadMissing('jadwal');
        $tanggal = $sesi->tanggal_pelajaran->toDateString();

        return Siswa::query()
            ->with('pengguna')
            ->whereHas('riwayatKelas', function ($q) use ($sesi, $tanggal) {
                $q->where('id_kelas', $sesi->jadwal->id_kelas)
                    ->whereDate('tanggal_mulai', '<=', $tanggal)
                    ->where(function ($qq) use ($tanggal) {
                        $qq->whereNull('tanggal_selesai')
                            ->orWhereDate('tanggal_selesai', '>=', $tanggal);
                    });
            })
            ->orderBy('nama_lengkap')
            ->get();
    }

    public function jendelaTerbuka(SesiAbsensi $sesi, ?Carbon $waktu = null): bool
    {
        $waktu ??= now();

        return $waktu->betweenIncluded($sesi->waktu_absensi_dibuka, $sesi->waktu_absensi_ditutup);
    }

    public function bolehMengisi(SesiAbsensi $sesi, Pengguna $pengguna): bool
    {
        $guru = $pengguna->guru;

        if (! $guru) {
            return false;
        }

        if (! $this->jendelaTerbuka($sesi)) {
            return false;
        }

        if (! in_array($sesi->status_sesi, [
            SesiAbsensi::STATUS_MENUNGGU,
            SesiAbsensi::STATUS_AKTIF,
            SesiAbsensi::STATUS_MENUNGGU_KONFIRMASI,
        ], true)) {
            return false;
        }

        if ($sesi->waktu_disimpan !== null && now()->gt($sesi->waktu_absensi_ditutup)) {
            return false;
        }

        if ($sesi->id_guru_pelaksana !== null && $sesi->id_guru_pelaksana !== $guru->id_guru) {
            return false;
        }

        if ($sesi->id_guru_pelaksana === $guru->id_guru) {
            return true;
        }

        if ($sesi->id_guru_penanggung_jawab === $guru->id_guru) {
            return $this->guruPunyaPenugasan($sesi, $guru);
        }

        return $this->piket->adalahPiket($pengguna, $sesi->tanggal_pelajaran);
    }

    protected function guruPunyaPenugasan(SesiAbsensi $sesi, Guru $guru): bool
    {
        $sesi->loadMissing('jadwal.versiJadwal');

        return PenugasanGuru::query()
            ->where('id_guru', $guru->id_guru)
            ->where('id_mata_pelajaran', $sesi->jadwal->id_mata_pelajaran)
            ->where('id_kelas', $sesi->jadwal->id_kelas)
            ->where('id_semester', $sesi->jadwal->versiJadwal->id_semester)
            ->where('status_aktif', true)
            ->whereDate('tanggal_mulai', '<=', $sesi->tanggal_pelajaran)
            ->where(function ($q) use ($sesi) {
                $q->whereNull('tanggal_selesai')
                    ->orWhereDate('tanggal_selesai', '>=', $sesi->tanggal_pelajaran);
            })
            ->exists();
    }

    /**
     * @param  array<int|string, mixed>  $data
     */
    public function simpan(
        SesiAbsensi $sesi,
        array $data,
        string $metode,
        Pengguna $pengguna,
        ?string $alasan = null,
        bool $koreksiAdmin = false,
    ): SesiAbsensi {
        if ($koreksiAdmin && ! $pengguna->isAdmin()) {
            throw ValidationException::withMessages(['absensi' => 'Hanya admin yang dapat melakukan koreksi khusus.']);
        }

        $data = $this->normalisasiData($data);

        $hasil = DB::transaction(function () use ($sesi, $data, $metode, $pengguna, $alasan, $koreksiAdmin) {
            $sesi = SesiAbsensi::query()
                ->with(['jadwal.kelas', 'jadwal.mataPelajaran', 'jadwal.versiJadwal.semester'])
                ->lockForUpdate()
                ->findOrFail($sesi->id_sesi_absensi);

            if ($sesi->status_sesi === SesiAbsensi::STATUS_DIBATALKAN) {
                throw ValidationException::withMessages(['absensi' => 'Sesi ini sudah dibatalkan.']);
            }

            if (! $koreksiAdmin) {
                if (! $pengguna->isGuru()) {
                    throw ValidationException::withMessages(['absensi' => 'Hanya guru yang dapat mengisi absensi.']);
                }

                if (! $this->bolehMengisi($sesi, $pengguna)) {
                    throw ValidationException::withMessages([
                        'absensi' => 'Absensi tidak dapat diisi di luar jendela waktu atau Anda tidak berhak atas sesi ini.',
                    ]);
                }

                if (in_array($sesi->status_sesi, [
                    SesiAbsensi::STATUS_TERKONFIRMASI,
                    SesiAbsensi::STATUS_PERLU_PEMERIKSAAN,
                    SesiAbsensi::STATUS_BELUM_DIKONFIRMASI,
                    SesiAbsensi::STATUS_DITUTUP,
                ], true)) {
                    throw ValidationException::withMessages(['absensi' => 'Sesi ini sudah dikunci.']);
                }
            }

            $siswa = $this->siswaPadaSesi($sesi);
            $this->pastikanSemuaSiswaTerisi($siswa, $data);

            $pertamaKali = $sesi->waktu_disimpan === null;
            $statusLamaSesi = $sesi->status_sesi;
            $berubah = [];

            foreach ($siswa as $murid) {
                $baris = $data[$murid->id_siswa];
                $status = $baris['status'];
                $keterangan = $baris['keterangan'];

                $catatan = CatatanAbsensi::query()
                    ->where('id_sesi_absensi', $sesi->id_sesi_absensi)
                    ->where('id_siswa', $murid->id_siswa)
                    ->lockForUpdate()
                    ->first();

                $statusSebelumnya = $catatan?->status_kehadiran;

                $metodeCatatan = $metode === SesiAbsensi::METODE_QR
                    ? CatatanAbsensi::METODE_QR
                    : CatatanAbsensi::METODE_MANUAL;
                $waktuKehadiran = $status === CatatanAbsensi::HADIR ? now() : null;

                if ($catatan && $catatan->metode_absensi === CatatanAbsensi::METODE_QR && $status === CatatanAbsensi::HADIR) {
                    $metodeCatatan = CatatanAbsensi::METODE_QR;
                    $waktuKehadiran = $catatan->waktu_kehadiran ?? now();
                } elseif ($catatan && $catatan->status_kehadiran === CatatanAbsensi::HADIR && $status === CatatanAbsensi::HADIR) {
                    $waktuKehadiran = $catatan->waktu_kehadiran ?? now();
                }

                if (! $catatan) {
                    $catatan = CatatanAbsensi::create([
                        'id_sesi_absensi' => $sesi->id_sesi_absensi,
                        'id_siswa' => $murid->id_siswa,
                        'status_kehadiran' => $status,
                        'metode_absensi' => $metodeCatatan,
                        'waktu_kehadiran' => $waktuKehadiran,
                        'id_pengguna_pencatat' => $pengguna->id_pengguna,
                        'keterangan' => $keterangan,
                    ]);
                } else {
                    $catatan->update([
                        'status_kehadiran' => $status,
                        'metode_absensi' => $metodeCatatan,
                        'waktu_kehadiran' => $waktuKehadiran,
                        'id_pengguna_pencatat' => $pengguna->id_pengguna,
                        'keterangan' => $keterangan,
                    ]);
                }

                if ($statusSebelumnya !== $status) {
                    AuditAbsensi::create([
                        'id_catatan_absensi' => $catatan->id_catatan_absensi,
                        'id_pengguna' => $pengguna->id_pengguna,
                        'status_sebelumnya' => $statusSebelumnya,
                        'status_sesudahnya' => $status,
                        'alasan_perubahan' => $alasan
                            ?? ($statusSebelumnya === null ? 'Pengisian absensi' : 'Perubahan status absensi'),
                        'waktu_perubahan' => now(),
                    ]);

                    $berubah[] = $murid->id_siswa;
                }
            }

            $metodeSesi = $this->metodeSesi($sesi->id_sesi_absensi);

            $guru = $pengguna->guru;
            $pelaksana = $sesi->id_guru_pelaksana;
            $jenis = $sesi->jenis_pelaksana;
            $ambilAlihPada = $sesi->diambil_alih_pada;
            $alasanAmbilAlih = $sesi->alasan_pengambilalihan;

            if (! $koreksiAdmin && $guru) {
                if ($pelaksana === null) {
                    $pelaksana = $guru->id_guru;
                    $jenis = $guru->id_guru === $sesi->id_guru_penanggung_jawab
                        ? SesiAbsensi::PELAKSANA_PENGAMPU
                        : SesiAbsensi::PELAKSANA_PIKET;

                    if ($jenis === SesiAbsensi::PELAKSANA_PIKET) {
                        $ambilAlihPada = now();
                        $alasanAmbilAlih = 'Pengisian langsung oleh guru piket.';

                        $this->log->catat(
                            $pengguna,
                            'ambil_alih',
                            'Guru Piket',
                            "Mengisi langsung sesi {$sesi->jadwal?->mataPelajaran?->nama_mata_pelajaran} sebagai guru piket.",
                            'sesi_absensi',
                            $sesi->id_sesi_absensi
                        );
                    }
                } elseif ($pelaksana !== $guru->id_guru) {
                    throw ValidationException::withMessages(['absensi' => 'Sesi ini sedang dilaksanakan oleh guru lain.']);
                }
            }

            $final = in_array($statusLamaSesi, [
                SesiAbsensi::STATUS_TERKONFIRMASI,
                SesiAbsensi::STATUS_PERLU_PEMERIKSAAN,
                SesiAbsensi::STATUS_BELUM_DIKONFIRMASI,
                SesiAbsensi::STATUS_DITUTUP,
            ], true);

            $statusBaru = ($koreksiAdmin && $final) ? $statusLamaSesi : SesiAbsensi::STATUS_MENUNGGU_KONFIRMASI;

            $sesi->update([
                'id_guru_pelaksana' => $pelaksana,
                'jenis_pelaksana' => $jenis,
                'metode_absensi' => $metodeSesi,
                'status_sesi' => $statusBaru,
                'waktu_disimpan' => $sesi->waktu_disimpan ?? now(),
                'diambil_alih_pada' => $ambilAlihPada,
                'alasan_pengambilalihan' => $alasanAmbilAlih,
            ]);

            $this->log->catat(
                $pengguna,
                $koreksiAdmin ? 'koreksi_absensi' : 'simpan_absensi',
                $koreksiAdmin ? 'Koreksi Absensi' : 'Absensi',
                ($koreksiAdmin ? 'Koreksi absensi' : 'Menyimpan absensi')
                    ." {$sesi->jadwal?->mataPelajaran?->nama_mata_pelajaran} kelas {$sesi->jadwal?->kelas?->nama_kelas}.",
                'sesi_absensi',
                $sesi->id_sesi_absensi,
                ['status_sesi' => $statusLamaSesi],
                [
                    'status_sesi' => $statusBaru,
                    'metode_absensi' => $metodeSesi,
                    'jumlah_perubahan' => count($berubah),
                    'alasan' => $alasan,
                ]
            );

            return compact('sesi', 'pertamaKali', 'berubah');
        });

        foreach ($hasil['berubah'] as $idSiswa) {
            $this->rekap->hitungUlangHarian($idSiswa, $hasil['sesi']->tanggal_pelajaran);
        }

        if ($hasil['pertamaKali'] && $hasil['sesi']->status_sesi === SesiAbsensi::STATUS_MENUNGGU_KONFIRMASI) {
            $this->konfirmasi->mulai($hasil['sesi']);
        }

        return $hasil['sesi']->fresh([
            'jadwal.kelas',
            'jadwal.mataPelajaran',
            'guruPelaksana',
            'guruPenanggungJawab',
        ]);
    }

    /**
     * @return array{status: string, pesan: string, siswa: ?Siswa, waktu: ?Carbon}
     */
    public function catatQr(SesiAbsensi $sesi, string $token, Pengguna $pengguna): array
    {
        $verifikasi = $this->qr->verifikasi($token);

        if ($verifikasi['status'] !== 'valid' || ! $verifikasi['kode']) {
            return [
                'status' => $verifikasi['status'],
                'pesan' => $verifikasi['pesan'],
                'siswa' => null,
                'waktu' => null,
            ];
        }

        $kode = $verifikasi['kode'];

        $sesi = SesiAbsensi::query()
            ->with(['jadwal.kelas', 'jadwal.mataPelajaran', 'jadwal.versiJadwal.semester', 'guruPelaksana'])
            ->findOrFail($sesi->id_sesi_absensi);

        if (! $pengguna->isAdmin() && ! $this->bolehMengisi($sesi, $pengguna)) {
            return [
                'status' => 'tidak_berhak',
                'pesan' => 'Anda tidak berhak memindai QR pada sesi ini atau jendela absensi belum/telah tertutup.',
                'siswa' => null,
                'waktu' => null,
            ];
        }

        if (in_array($sesi->status_sesi, [SesiAbsensi::STATUS_DIBATALKAN, SesiAbsensi::STATUS_DITUTUP], true)
            || now()->gt($sesi->waktu_absensi_ditutup)
            || now()->lt($sesi->waktu_absensi_dibuka)) {
            return [
                'status' => now()->gt($sesi->waktu_absensi_ditutup) ? 'sesi_ditutup' : 'sesi_tidak_aktif',
                'pesan' => 'Sesi absensi tidak sedang aktif.',
                'siswa' => null,
                'waktu' => null,
            ];
        }

        $murid = $kode->siswa;

        if (! $murid || $murid->status_siswa !== 'aktif') {
            return [
                'status' => 'tidak_valid',
                'pesan' => 'Siswa tidak aktif.',
                'siswa' => null,
                'waktu' => null,
            ];
        }

        $siswaSesi = $this->siswaPadaSesi($sesi);

        if (! $siswaSesi->contains('id_siswa', $murid->id_siswa)) {
            return [
                'status' => 'kelas_lain',
                'pesan' => "Siswa {$murid->nama_lengkap} bukan bagian dari kelas ini.",
                'siswa' => $murid,
                'waktu' => null,
            ];
        }

        $hasil = DB::transaction(function () use ($sesi, $murid, $pengguna) {
            $sesi = SesiAbsensi::query()->lockForUpdate()->findOrFail($sesi->id_sesi_absensi);

            if (now()->gt($sesi->waktu_absensi_ditutup) || now()->lt($sesi->waktu_absensi_dibuka)) {
                return ['status' => 'sesi_ditutup', 'pesan' => 'Sesi absensi sudah ditutup.', 'siswa' => $murid, 'waktu' => null];
            }

            $catatan = CatatanAbsensi::query()
                ->where('id_sesi_absensi', $sesi->id_sesi_absensi)
                ->where('id_siswa', $murid->id_siswa)
                ->lockForUpdate()
                ->first();

            if ($catatan) {
                return [
                    'status' => 'sudah_diabsen',
                    'pesan' => "Siswa {$murid->nama_lengkap} sudah memiliki catatan absensi ({$catatan->status_kehadiran}).",
                    'siswa' => $murid,
                    'waktu' => $catatan->waktu_kehadiran,
                ];
            }

            $catatan = CatatanAbsensi::create([
                'id_sesi_absensi' => $sesi->id_sesi_absensi,
                'id_siswa' => $murid->id_siswa,
                'status_kehadiran' => CatatanAbsensi::HADIR,
                'metode_absensi' => CatatanAbsensi::METODE_QR,
                'waktu_kehadiran' => now(),
                'id_pengguna_pencatat' => $pengguna->id_pengguna,
                'keterangan' => 'Pemindaian QR Code',
            ]);

            AuditAbsensi::create([
                'id_catatan_absensi' => $catatan->id_catatan_absensi,
                'id_pengguna' => $pengguna->id_pengguna,
                'status_sebelumnya' => null,
                'status_sesudahnya' => CatatanAbsensi::HADIR,
                'alasan_perubahan' => 'Pemindaian QR Code',
                'waktu_perubahan' => now(),
            ]);

            $guru = $pengguna->guru;

            if ($guru && $sesi->id_guru_pelaksana === null) {
                $jenis = $guru->id_guru === $sesi->id_guru_penanggung_jawab
                    ? SesiAbsensi::PELAKSANA_PENGAMPU
                    : SesiAbsensi::PELAKSANA_PIKET;

                $sesi->id_guru_pelaksana = $guru->id_guru;
                $sesi->jenis_pelaksana = $jenis;

                if ($jenis === SesiAbsensi::PELAKSANA_PIKET) {
                    $sesi->diambil_alih_pada = now();
                    $sesi->alasan_pengambilalihan = 'Pemindaian QR oleh guru piket.';
                }
            }

            $sesi->metode_absensi = $this->metodeSesi($sesi->id_sesi_absensi);

            if ($sesi->status_sesi === SesiAbsensi::STATUS_MENUNGGU) {
                $sesi->status_sesi = SesiAbsensi::STATUS_AKTIF;
            }

            $sesi->save();

            $this->log->catat(
                $pengguna,
                'scan_qr',
                'Absensi QR',
                "Memindai QR siswa {$murid->nama_lengkap} pada sesi {$sesi->id_sesi_absensi}.",
                'catatan_absensi',
                $catatan->id_catatan_absensi
            );

            return [
                'status' => 'hadir',
                'pesan' => "Kehadiran {$murid->nama_lengkap} berhasil dicatat.",
                'siswa' => $murid,
                'waktu' => $catatan->waktu_kehadiran,
            ];
        });

        if ($hasil['status'] === 'hadir') {
            $this->rekap->hitungUlangHarian($murid->id_siswa, $sesi->tanggal_pelajaran);
        }

        return $hasil;
    }

    public function finalisasiQr(SesiAbsensi $sesi, Pengguna $pengguna): SesiAbsensi
    {
        $siswa = $this->siswaPadaSesi($sesi);
        $catatan = CatatanAbsensi::query()
            ->where('id_sesi_absensi', $sesi->id_sesi_absensi)
            ->get()
            ->keyBy('id_siswa');

        $data = [];
        $belum = [];

        foreach ($siswa as $murid) {
            $baris = $catatan->get($murid->id_siswa);

            if (! $baris) {
                $belum[] = $murid->nama_lengkap;

                continue;
            }

            $data[$murid->id_siswa] = [
                'status' => $baris->status_kehadiran,
                'keterangan' => $baris->keterangan,
            ];
        }

        if ($belum) {
            throw ValidationException::withMessages([
                'absensi' => 'Masih ada siswa yang belum dipindai: '.implode(', ', array_slice($belum, 0, 8))
                    .(count($belum) > 8 ? ' dan lainnya.' : '.'),
            ]);
        }

        return $this->simpan($sesi, $data, SesiAbsensi::METODE_QR, $pengguna);
    }

    public function batalkan(SesiAbsensi $sesi, Pengguna $admin, string $alasan): SesiAbsensi
    {
        if (! $admin->isAdmin()) {
            throw ValidationException::withMessages(['sesi' => 'Hanya admin yang dapat membatalkan sesi.']);
        }

        $siswa = $this->siswaPadaSesi($sesi);

        $sesi = DB::transaction(function () use ($sesi, $admin, $alasan) {
            $sesi = SesiAbsensi::query()->lockForUpdate()->findOrFail($sesi->id_sesi_absensi);

            if ($sesi->catatanAbsensi()->exists()) {
                throw ValidationException::withMessages([
                    'sesi' => 'Sesi yang sudah memiliki catatan absensi tidak boleh dibatalkan. Gunakan koreksi absensi.',
                ]);
            }

            $sesi->update([
                'status_sesi' => SesiAbsensi::STATUS_DIBATALKAN,
                'alasan_pembatalan' => $alasan,
            ]);

            $this->log->catat(
                $admin,
                'batalkan_sesi',
                'Sesi Absensi',
                "Membatalkan sesi {$sesi->id_sesi_absensi}.",
                'sesi_absensi',
                $sesi->id_sesi_absensi,
                ['status_sesi' => $sesi->getOriginal('status_sesi')],
                ['status_sesi' => SesiAbsensi::STATUS_DIBATALKAN, 'alasan' => $alasan]
            );

            return $sesi;
        });

        foreach ($siswa as $murid) {
            $this->rekap->hitungUlangHarian($murid->id_siswa, $sesi->tanggal_pelajaran);
        }

        return $sesi;
    }

    /**
     * @param  array<int|string, mixed>  $data
     * @return array<int, array{status: string, keterangan: ?string}>
     */
    protected function normalisasiData(array $data): array
    {
        $hasil = [];

        foreach ($data as $idSiswa => $baris) {
            $status = is_array($baris) ? ($baris['status'] ?? null) : $baris;
            $keterangan = is_array($baris) ? ($baris['keterangan'] ?? null) : null;

            if (! in_array($status, [CatatanAbsensi::HADIR, CatatanAbsensi::IZIN, CatatanAbsensi::SAKIT, CatatanAbsensi::ALPHA], true)) {
                throw ValidationException::withMessages([
                    'absensi' => "Status kehadiran untuk siswa ID {$idSiswa} tidak valid.",
                ]);
            }

            $hasil[(int) $idSiswa] = [
                'status' => $status,
                'keterangan' => $keterangan !== null && $keterangan !== '' ? mb_substr((string) $keterangan, 0, 255) : null,
            ];
        }

        return $hasil;
    }

    /**
     * @param  Collection<int, Siswa>  $siswa
     * @param  array<int, array{status: string, keterangan: ?string}>  $data
     */
    protected function pastikanSemuaSiswaTerisi(Collection $siswa, array $data): void
    {
        $idSiswa = $siswa->pluck('id_siswa')->sort()->values()->all();
        $idData = collect(array_keys($data))->sort()->values()->all();

        if ($idSiswa !== $idData) {
            $belum = $siswa
                ->whereNotIn('id_siswa', $idData)
                ->pluck('nama_lengkap')
                ->take(8)
                ->implode(', ');

            throw ValidationException::withMessages([
                'absensi' => $belum
                    ? "Masih ada siswa yang belum diisi: {$belum}."
                    : 'Data siswa tidak sesuai dengan kelas pada sesi ini.',
            ]);
        }
    }

    protected function metodeSesi(int $idSesi): ?string
    {
        $metode = CatatanAbsensi::query()
            ->where('id_sesi_absensi', $idSesi)
            ->distinct()
            ->pluck('metode_absensi')
            ->all();

        return match (count($metode)) {
            0 => null,
            1 => $metode[0],
            default => SesiAbsensi::METODE_GABUNGAN,
        };
    }
}
