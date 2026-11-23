<?php

namespace App\Services;

use App\Models\Notifikasi;
use App\Models\Pengguna;

class LayananNotifikasi
{
    public function kirim(
        Pengguna $penerima,
        string $judul,
        string $pesan,
        string $jenis = 'sistem',
        ?string $url = null,
    ): Notifikasi {
        return Notifikasi::create([
            'id_pengguna' => $penerima->id_pengguna,
            'judul' => $judul,
            'pesan' => $pesan,
            'jenis' => $jenis,
            'url' => $url,
            'sudah_dibaca' => false,
        ]);
    }

    public function kirimKeAdmin(
        string $judul,
        string $pesan,
        string $jenis = 'sistem',
        ?string $url = null,
    ): void {
        $admin = Pengguna::query()
            ->where('peran', 'admin')
            ->where('status_aktif', true)
            ->pluck('id_pengguna');

        if ($admin->isEmpty()) {
            return;
        }

        $waktu = now();

        Notifikasi::query()->insert(
            $admin->map(fn (int $id) => [
                'id_pengguna' => $id,
                'judul' => $judul,
                'pesan' => $pesan,
                'jenis' => $jenis,
                'url' => $url,
                'sudah_dibaca' => false,
                'dibuat_pada' => $waktu,
            ])->all()
        );
    }
}
