<?php

namespace App\Services;

use App\Models\Pengguna;
use App\Models\PengaturanSistem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LayananPengaturan
{
    public const BAWAAN = [
        'menit_buka_sebelum_selesai' => 15,
        'menit_tutup_setelah_selesai' => 0,
        'menit_batas_konfirmasi' => 5,
        'detik_per_alihan_konfirmasi' => 60,
        'zona_waktu' => 'Asia/Makassar',
        'kunci_absensi_otomatis' => true,
        'pengalihan_konfirmasi_otomatis' => true,
    ];

    public const LABEL = [
        'menit_buka_sebelum_selesai' => 'Absensi dibuka sebelum pelajaran berakhir (menit)',
        'menit_tutup_setelah_selesai' => 'Absensi ditutup setelah pelajaran berakhir (menit)',
        'menit_batas_konfirmasi' => 'Batas konfirmasi siswa setelah pelajaran berakhir (menit)',
        'detik_per_alihan_konfirmasi' => 'Jeda pengalihan ke petugas berikutnya (detik)',
        'zona_waktu' => 'Zona waktu aplikasi',
        'kunci_absensi_otomatis' => 'Kunci absensi otomatis saat waktu tutup',
        'pengalihan_konfirmasi_otomatis' => 'Alihkan konfirmasi otomatis ke petugas berikutnya',
    ];

    /** @var array<string, mixed> */
    protected array $nilaiTersimpan = [];

    protected ?string $tanggalCache = null;

    /**
     * @return array<string, mixed>
     */
    public function semua(?Carbon $tanggal = null): array
    {
        $tanggal = ($tanggal ?? now())->copy()->startOfDay();
        $kunciCache = $tanggal->toDateString();

        if ($this->tanggalCache === $kunciCache) {
            return $this->nilaiTersimpan;
        }

        $baris = PengaturanSistem::query()
            ->where('status_aktif', true)
            ->whereDate('tanggal_berlaku', '<=', $kunciCache)
            ->orderBy('tanggal_berlaku')
            ->orderBy('id_pengaturan')
            ->get()
            ->keyBy('kunci');

        $nilai = self::BAWAAN;

        foreach ($baris as $kunci => $pengaturan) {
            $nilai[$kunci] = $this->ubahTipe($pengaturan->nilai, $pengaturan->tipe);
        }

        $this->nilaiTersimpan = $nilai;
        $this->tanggalCache = $kunciCache;

        return $nilai;
    }

    public function nilai(string $kunci, mixed $bawaan = null): mixed
    {
        $semua = $this->semua();

        return $semua[$kunci] ?? $bawaan ?? self::BAWAAN[$kunci] ?? null;
    }

    public function zonaWaktu(): string
    {
        $zona = (string) $this->nilai('zona_waktu', 'Asia/Makassar');

        return in_array($zona, timezone_identifiers_list(), true) ? $zona : 'Asia/Makassar';
    }

    /**
     * @param  array<string, mixed>  $nilai
     */
    public function simpan(array $nilai, Pengguna $admin): void
    {
        $izin = array_keys(self::BAWAAN);

        DB::transaction(function () use ($nilai, $admin, $izin) {
            foreach ($nilai as $kunci => $isi) {
                if (! in_array($kunci, $izin, true)) {
                    continue;
                }

                $bawaan = self::BAWAAN[$kunci];
                $tipe = is_bool($bawaan) ? 'boolean' : (is_int($bawaan) ? 'angka' : 'teks');

                if ($tipe === 'boolean') {
                    $isi = filter_var($isi, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
                }

                PengaturanSistem::query()->updateOrCreate(
                    [
                        'kunci' => $kunci,
                        'tanggal_berlaku' => now()->toDateString(),
                    ],
                    [
                        'nilai' => (string) $isi,
                        'tipe' => $tipe,
                        'deskripsi' => self::LABEL[$kunci] ?? null,
                        'status_aktif' => true,
                        'dibuat_oleh' => $admin->id_pengguna,
                    ]
                );
            }
        });
    }

    protected function ubahTipe(?string $nilai, string $tipe): mixed
    {
        return match ($tipe) {
            'angka' => (int) $nilai,
            'boolean' => filter_var($nilai, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode((string) $nilai, true) ?? [],
            default => $nilai,
        };
    }
}
