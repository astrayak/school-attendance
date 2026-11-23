<?php

namespace App\Services;

use App\Models\Semester;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class LayananPeriode
{
    private ?Semester $aktifCache = null;

    private bool $aktifLoaded = false;

    public function aktif(): ?Semester
    {
        if ($this->aktifLoaded) {
            return $this->aktifCache;
        }

        $this->aktifLoaded = true;

        $hariIni = now()->startOfDay();

        // 1. Semester yang ditandai aktif dan sedang berjalan berdasarkan tanggal.
        $semester = Semester::query()
            ->with('tahunAjaran')
            ->where('status_aktif', true)
            ->whereDate('tanggal_mulai', '<=', $hariIni)
            ->whereDate('tanggal_selesai', '>=', $hariIni)
            ->orderByDesc('tanggal_mulai')
            ->first();

        // 2. Jika tidak ada, pakai semester yang memuat tanggal hari ini
        //    (mis. semester ditandai aktif untuk periode yang belum dimulai).
        if (! $semester) {
            $semester = Semester::query()
                ->with('tahunAjaran')
                ->whereDate('tanggal_mulai', '<=', $hariIni)
                ->whereDate('tanggal_selesai', '>=', $hariIni)
                ->orderByDesc('tanggal_mulai')
                ->first();
        }

        // 3. Fallback terakhir: semester yang ditandai aktif (mis. belum ada
        //    semester yang memuat tanggal hari ini).
        if (! $semester) {
            $semester = Semester::query()
                ->with('tahunAjaran')
                ->where('status_aktif', true)
                ->orderByDesc('tanggal_mulai')
                ->first();
        }

        return $this->aktifCache = $semester;
    }

    /**
     * @return Collection<int, Semester>
     */
    public function semua(): Collection
    {
        return Semester::query()
            ->with('tahunAjaran')
            ->orderByDesc('tanggal_mulai')
            ->get();
    }

    public function idAktif(): ?int
    {
        return $this->aktif()?->id_semester;
    }

    public function label(?Semester $semester): string
    {
        if (! $semester) {
            return 'Belum ada semester aktif';
        }

        $tahun = $semester->tahunAjaran?->nama_tahun_ajaran;

        return trim(ucfirst((string) $semester->nama_semester).' '.($tahun ?? ''));
    }

    /**
     * Rentang tanggal semester yang sudah berjalan. Bila semester belum dimulai,
     * rentang dibuat kosong agar tidak menampilkan data periode lain.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function rentang(?Semester $semester): array
    {
        if (! $semester) {
            return [now()->startOfMonth(), now()->startOfDay()];
        }

        $dari = $semester->tanggal_mulai?->copy()->startOfDay() ?? now()->startOfMonth();
        $sampai = $semester->tanggal_selesai?->copy()->startOfDay() ?? now()->startOfDay();
        $hariIni = now()->startOfDay();

        if ($sampai->gt($hariIni)) {
            $sampai = $hariIni->copy();
        }

        if ($dari->gt($sampai)) {
            $sampai = $dari->copy();
        }

        return [$dari, $sampai];
    }

    public function mencakup(?Semester $semester, Carbon $tanggal): bool
    {
        if (! $semester?->tanggal_mulai || ! $semester?->tanggal_selesai) {
            return false;
        }

        return $tanggal->copy()->startOfDay()->betweenIncluded(
            $semester->tanggal_mulai->copy()->startOfDay(),
            $semester->tanggal_selesai->copy()->startOfDay(),
        );
    }
}
