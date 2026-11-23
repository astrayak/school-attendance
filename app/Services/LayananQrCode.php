<?php

namespace App\Services;

use App\Models\KodeQrSiswa;
use App\Models\Pengguna;
use App\Models\Siswa;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Throwable;

class LayananQrCode
{
    public const AWALAN_TOKEN = 'ABS1.';

    public function __construct(private readonly LayananLogAktivitas $log)
    {
    }

    /**
     * @return array{token: string, kode: KodeQrSiswa}
     */
    public function buat(Siswa $siswa, ?Pengguna $pembuat = null): array
    {
        return DB::transaction(function () use ($siswa, $pembuat) {
            KodeQrSiswa::query()
                ->where('id_siswa', $siswa->id_siswa)
                ->where('status_aktif', true)
                ->update([
                    'status_aktif' => false,
                    'tanggal_dicabut' => now(),
                    'diperbarui_pada' => now(),
                ]);

            $token = self::AWALAN_TOKEN.rtrim(
                strtr(base64_encode(random_bytes(32)), '+/', '-_'),
                '='
            );

            $kode = KodeQrSiswa::create([
                'id_siswa' => $siswa->id_siswa,
                'token_hash' => hash('sha256', $token),
                'token_terenkripsi' => Crypt::encryptString($token),
                'status_aktif' => true,
                'tanggal_dibuat' => now(),
            ]);

            if ($pembuat) {
                $this->log->catat(
                    $pembuat,
                    'buat_qr',
                    'QR Code',
                    "Membuat QR Code baru untuk siswa {$siswa->nama_lengkap}.",
                    'kode_qr_siswa',
                    $kode->id_kode_qr
                );
            }

            return ['token' => $token, 'kode' => $kode];
        });
    }

    public function pastikanAktif(Siswa $siswa, ?Pengguna $pembuat = null): KodeQrSiswa
    {
        $kode = KodeQrSiswa::query()
            ->where('id_siswa', $siswa->id_siswa)
            ->where('status_aktif', true)
            ->latest('id_kode_qr')
            ->first();

        if ($kode && $this->token($kode) !== null) {
            return $kode;
        }

        return $this->buat($siswa, $pembuat)['kode'];
    }

    public function token(KodeQrSiswa $kode): ?string
    {
        if (! $kode->token_terenkripsi) {
            return null;
        }

        try {
            return Crypt::decryptString($kode->token_terenkripsi);
        } catch (Throwable) {
            return null;
        }
    }

    public function gambarSvg(KodeQrSiswa $kode, int $ukuran = 320): ?string
    {
        $token = $this->token($kode);

        if (! $token) {
            return null;
        }

        $renderer = new ImageRenderer(
            new RendererStyle($ukuran, 2),
            new SvgImageBackEnd()
        );

        return (new Writer($renderer))->writeString($token);
    }

    /**
     * @return array{status: string, kode: ?KodeQrSiswa, pesan: string}
     */
    public function verifikasi(string $token): array
    {
        $token = trim($token);

        if (! str_starts_with($token, self::AWALAN_TOKEN) || strlen($token) < 20 || strlen($token) > 120) {
            return ['status' => 'tidak_valid', 'kode' => null, 'pesan' => 'Format QR tidak dikenali.'];
        }

        $hash = hash('sha256', $token);

        $kode = KodeQrSiswa::query()
            ->with('siswa')
            ->where('token_hash', $hash)
            ->first();

        if (! $kode) {
            return ['status' => 'tidak_valid', 'kode' => null, 'pesan' => 'QR Code tidak terdaftar.'];
        }

        if (! $kode->status_aktif || $kode->tanggal_dicabut !== null) {
            return ['status' => 'dicabut', 'kode' => $kode, 'pesan' => 'QR Code sudah dicabut.'];
        }

        return ['status' => 'valid', 'kode' => $kode, 'pesan' => 'QR Code valid.'];
    }

    public function cabut(KodeQrSiswa $kode, Pengguna $pembuat): void
    {
        DB::transaction(function () use ($kode, $pembuat) {
            $kode->update([
                'status_aktif' => false,
                'tanggal_dicabut' => now(),
            ]);

            $this->log->catat(
                $pembuat,
                'cabut_qr',
                'QR Code',
                "Mencabut QR Code siswa {$kode->siswa?->nama_lengkap}.",
                'kode_qr_siswa',
                $kode->id_kode_qr
            );
        });
    }
}
