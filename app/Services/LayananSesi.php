<?php

namespace App\Services;

use App\Models\SesiAbsensi;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class LayananSesi
{
    public function __construct(
        private readonly LayananJadwal $jadwal,
        private readonly LayananKonfirmasi $konfirmasi,
        private readonly LayananNotifikasi $notifikasi,
    ) {
    }

    public function sinkronkanTanggal(Carbon $tanggal): Collection
    {
        $daftar = $this->jadwal->pastikanSesiTanggal($tanggal);

        foreach ($daftar as $sesi) {
            $this->perbaruiStatusSesi($sesi);
        }

        return $daftar;
    }

    public function perbaruiStatusSesi(SesiAbsensi $sesi): void
    {
        if (in_array($sesi->status_sesi, [
            SesiAbsensi::STATUS_TERKONFIRMASI,
            SesiAbsensi::STATUS_PERLU_PEMERIKSAAN,
            SesiAbsensi::STATUS_BELUM_DIKONFIRMASI,
            SesiAbsensi::STATUS_DIBATALKAN,
        ], true)) {
            return;
        }

        if ($sesi->waktu_disimpan !== null) {
            return;
        }

        if ($sesi->status_sesi === SesiAbsensi::STATUS_DITUTUP) {
            return;
        }

        $statusBaru = match (true) {
            now()->lt($sesi->waktu_absensi_dibuka) => SesiAbsensi::STATUS_MENUNGGU,
            now()->lte($sesi->waktu_absensi_ditutup) => SesiAbsensi::STATUS_AKTIF,
            default => SesiAbsensi::STATUS_DITUTUP,
        };

        if ($sesi->status_sesi === $statusBaru) {
            return;
        }

        $statusLama = $sesi->status_sesi;
        $sesi->update(['status_sesi' => $statusBaru]);

        if ($statusBaru === SesiAbsensi::STATUS_DITUTUP) {
            $this->notifikasi->kirimKeAdmin(
                'Sesi absensi terlewat',
                "Sesi {$sesi->jadwal?->mataPelajaran?->nama_mata_pelajaran} kelas "
                    .($sesi->jadwal?->kelas?->nama_kelas ?? '').' tanggal '
                    .$sesi->tanggal_pelajaran->format('d/m/Y').' ditutup tanpa pengisian absensi.',
                'absensi',
                route('admin.sesi.show', $sesi->id_sesi_absensi)
            );
        }
    }

    public function pemeliharaan(): void
    {
        $this->sinkronkanTanggal(now()->copy()->startOfDay());
        $this->sinkronkanTanggal(now()->copy()->subDay()->startOfDay());
        $this->konfirmasi->prosesKedaluwarsa();
    }

    public function sesiHariIni(): Collection
    {
        $this->sinkronkanTanggal(now()->copy()->startOfDay());

        return SesiAbsensi::query()
            ->with(['jadwal.kelas', 'jadwal.mataPelajaran', 'guruPenanggungJawab', 'guruPelaksana'])
            ->whereDate('tanggal_pelajaran', now()->toDateString())
            ->where('status_sesi', '!=', SesiAbsensi::STATUS_DIBATALKAN)
            ->orderBy('waktu_mulai')
            ->get();
    }

    /**
     * @return array<string, int>
     */
    public function ringkasanHariIni(): array
    {
        $hariIni = now()->toDateString();

        $dasar = SesiAbsensi::query()
            ->whereDate('tanggal_pelajaran', $hariIni)
            ->where('status_sesi', '!=', SesiAbsensi::STATUS_DIBATALKAN);

        return [
            'total_sesi' => (clone $dasar)->count(),
            'belum_diisi' => (clone $dasar)->whereNull('waktu_disimpan')->count(),
            'menunggu_konfirmasi' => (clone $dasar)->where('status_sesi', SesiAbsensi::STATUS_MENUNGGU_KONFIRMASI)->count(),
            'terkonfirmasi' => (clone $dasar)->where('status_sesi', SesiAbsensi::STATUS_TERKONFIRMASI)->count(),
            'perlu_pemeriksaan' => (clone $dasar)->where('status_sesi', SesiAbsensi::STATUS_PERLU_PEMERIKSAAN)->count(),
            'belum_dikonfirmasi' => (clone $dasar)->where('status_sesi', SesiAbsensi::STATUS_BELUM_DIKONFIRMASI)->count(),
        ];
    }
}
