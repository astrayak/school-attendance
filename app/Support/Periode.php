<?php

namespace App\Support;

use Carbon\Carbon;
use Throwable;

class Periode
{
    public static function tanggal(?string $nilai, ?Carbon $bawaan = null): Carbon
    {
        if (! is_string($nilai) || trim($nilai) === '') {
            return ($bawaan ?? now())->copy()->startOfDay();
        }

        try {
            return Carbon::parse($nilai)->startOfDay();
        } catch (Throwable) {
            return ($bawaan ?? now())->copy()->startOfDay();
        }
    }

    public static function akhir(?string $nilai, ?Carbon $bawaan = null): Carbon
    {
        if (! is_string($nilai) || trim($nilai) === '') {
            return ($bawaan ?? now())->copy()->startOfDay();
        }

        try {
            return Carbon::parse($nilai)->startOfDay();
        } catch (Throwable) {
            return ($bawaan ?? now())->copy()->startOfDay();
        }
    }

    /**
     * Batasi rentang agar tidak membebani laporan.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function rentang(?string $dari, ?string $sampai, int $hariMaksimal = 366): array
    {
        $awal = self::tanggal($dari, now()->startOfMonth());
        $akhir = self::akhir($sampai);

        if ($awal->gt($akhir)) {
            [$awal, $akhir] = [$akhir, $awal];
        }

        if ($awal->diffInDays($akhir) > $hariMaksimal) {
            $awal = $akhir->copy()->subDays($hariMaksimal);
        }

        return [$awal, $akhir];
    }
}
