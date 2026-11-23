<?php

namespace App\Services;

use App\Models\Guru;
use App\Models\JadwalGuruPiket;
use App\Models\Pengguna;
use App\Models\SesiAbsensi;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LayananGuruPiket
{
    public function __construct(
        private readonly LayananLogAktivitas $log,
        private readonly LayananNotifikasi $notifikasi,
    ) {
    }

    public function guru(Pengguna $pengguna): ?Guru
    {
        return $pengguna->guru;
    }

    public function penugasanAktif(Pengguna $pengguna, Carbon $tanggal): ?JadwalGuruPiket
    {
        $guru = $this->guru($pengguna);

        if (! $guru) {
            return null;
        }

        return JadwalGuruPiket::query()
            ->with('semester')
            ->where('id_guru', $guru->id_guru)
            ->where('status_aktif', true)
            ->where('hari', LayananJadwal::namaHari($tanggal))
            ->whereDate('tanggal_mulai_berlaku', '<=', $tanggal)
            ->where(function ($q) use ($tanggal) {
                $q->whereNull('tanggal_berakhir')
                    ->orWhereDate('tanggal_berakhir', '>=', $tanggal);
            })
            ->whereHas('semester', function ($q) use ($tanggal) {
                $q->whereDate('tanggal_mulai', '<=', $tanggal)
                    ->whereDate('tanggal_selesai', '>=', $tanggal);
            })
            ->first();
    }

    public function adalahPiket(Pengguna $pengguna, Carbon $tanggal): bool
    {
        return $this->penugasanAktif($pengguna, $tanggal) !== null;
    }

    public function sesiBelumDitangani(Pengguna $pengguna, Carbon $tanggal): Collection
    {
        $guru = $this->guru($pengguna);

        if (! $guru || ! $this->adalahPiket($pengguna, $tanggal)) {
            return collect();
        }

        return SesiAbsensi::query()
            ->with(['jadwal.kelas', 'jadwal.mataPelajaran', 'guruPenanggungJawab'])
            ->whereDate('tanggal_pelajaran', $tanggal->toDateString())
            ->whereNull('waktu_disimpan')
            ->whereIn('status_sesi', [SesiAbsensi::STATUS_MENUNGGU, SesiAbsensi::STATUS_AKTIF])
            ->where(function ($q) use ($guru) {
                $q->whereNull('id_guru_pelaksana')
                    ->orWhere('id_guru_pelaksana', $guru->id_guru);
            })
            ->orderBy('waktu_mulai')
            ->get();
    }

    public function ambilAlih(SesiAbsensi $sesi, Pengguna $pengguna, string $alasan): SesiAbsensi
    {
        $guru = $this->guru($pengguna);

        if (! $guru) {
            throw ValidationException::withMessages(['piket' => 'Akun ini bukan guru.']);
        }

        $hasil = DB::transaction(function () use ($sesi, $pengguna, $guru, $alasan) {
            $sesi = SesiAbsensi::query()->lockForUpdate()->findOrFail($sesi->id_sesi_absensi);

            if (! $this->adalahPiket($pengguna, $sesi->tanggal_pelajaran)) {
                throw ValidationException::withMessages([
                    'piket' => 'Anda tidak bertugas sebagai guru piket pada tanggal sesi ini.',
                ]);
            }

            if ($sesi->waktu_disimpan !== null) {
                throw ValidationException::withMessages([
                    'piket' => 'Sesi ini sudah diselesaikan guru pengampu dan tidak dapat diambil alih.',
                ]);
            }

            if ($sesi->id_guru_pelaksana !== null && $sesi->id_guru_pelaksana !== $guru->id_guru) {
                throw ValidationException::withMessages([
                    'piket' => 'Sesi ini sudah diambil alih oleh guru piket lain.',
                ]);
            }

            if (! in_array($sesi->status_sesi, [SesiAbsensi::STATUS_MENUNGGU, SesiAbsensi::STATUS_AKTIF], true)) {
                throw ValidationException::withMessages([
                    'piket' => 'Status sesi tidak memungkinkan untuk diambil alih.',
                ]);
            }

            if (now()->lt($sesi->waktu_absensi_dibuka) || now()->gt($sesi->waktu_absensi_ditutup)) {
                throw ValidationException::withMessages([
                    'piket' => 'Pengambilalihan hanya dapat dilakukan selama jendela absensi terbuka.',
                ]);
            }

            $sebelum = $sesi->only(['id_guru_pelaksana', 'jenis_pelaksana', 'status_sesi', 'diambil_alih_pada']);

            $sesi->update([
                'id_guru_pelaksana' => $guru->id_guru,
                'jenis_pelaksana' => SesiAbsensi::PELAKSANA_PIKET,
                'status_sesi' => SesiAbsensi::STATUS_AKTIF,
                'diambil_alih_pada' => now(),
                'alasan_pengambilalihan' => $alasan,
            ]);

            $this->log->catat(
                $pengguna,
                'ambil_alih',
                'Guru Piket',
                "Mengambil alih sesi {$sesi->jadwal?->mataPelajaran?->nama_mata_pelajaran} kelas {$sesi->jadwal?->kelas?->nama_kelas}.",
                'sesi_absensi',
                $sesi->id_sesi_absensi,
                $sebelum,
                $sesi->only(['id_guru_pelaksana', 'jenis_pelaksana', 'status_sesi', 'diambil_alih_pada'])
            );

            return $sesi;
        });

        if ($sesi->guruPenanggungJawab?->pengguna) {
            $this->notifikasi->kirim(
                $sesi->guruPenanggungJawab->pengguna,
                'Sesi diambil alih guru piket',
                "Sesi {$sesi->jadwal?->mataPelajaran?->nama_mata_pelajaran} kelas {$sesi->jadwal?->kelas?->nama_kelas} diambil alih oleh {$pengguna->nama_pengguna}. Alasan: {$alasan}",
                'absensi'
            );
        }

        return $hasil->fresh(['jadwal.kelas', 'jadwal.mataPelajaran', 'guruPelaksana']);
    }
}
