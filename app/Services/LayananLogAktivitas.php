<?php

namespace App\Services;

use App\Models\LogAktivitas;
use App\Models\Pengguna;
use Illuminate\Support\Str;

class LayananLogAktivitas
{
    /**
     * @param  array<string, mixed>|null  $sebelum
     * @param  array<string, mixed>|null  $sesudah
     */
    public function catat(
        Pengguna $pengguna,
        string $jenisAktivitas,
        string $namaModul,
        string $deskripsi,
        ?string $jenisData = null,
        int|string|null $idDataTerkait = null,
        ?array $sebelum = null,
        ?array $sesudah = null,
    ): LogAktivitas {
        $ip = app()->runningInConsole() ? null : request()?->ip();

        return LogAktivitas::create([
            'id_pengguna' => $pengguna->id_pengguna,
            'jenis_aktivitas' => $jenisAktivitas,
            'nama_modul' => $namaModul,
            'deskripsi_aktivitas' => Str::limit($deskripsi, 250),
            'jenis_data' => $jenisData,
            'id_data_terkait' => $idDataTerkait !== null ? (string) $idDataTerkait : null,
            'data_sebelumnya' => $sebelum,
            'data_sesudahnya' => $sesudah,
            'alamat_ip' => $ip,
            'waktu_aktivitas' => now(),
        ]);
    }
}
