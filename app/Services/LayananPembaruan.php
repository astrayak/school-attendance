<?php

namespace App\Services;

use App\Models\Pengguna;
use App\Models\RiwayatPembaruan;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class LayananPembaruan
{
    public function __construct(private readonly LayananPeriode $periode)
    {
    }

    /**
     * Pemeriksaan ringkas kondisi aplikasi untuk halaman pembaruan/troubleshooting.
     *
     * @return array<string, mixed>
     */
    public function pemeriksaan(): array
    {
        $dbOk = true;
        $dbPesan = 'Terhubung';

        try {
            DB::connection()->getPdo();
        } catch (Throwable $e) {
            $dbOk = false;
            $dbPesan = Str::limit($e->getMessage(), 160);
        }

        $migrasiTerakhir = null;

        try {
            $migrasiTerakhir = DB::table('migrations')
                ->orderByDesc('batch')
                ->orderByDesc('id')
                ->first();
        } catch (Throwable) {
            $migrasiTerakhir = null;
        }

        $viewFiles = glob(storage_path('framework/views/*.php')) ?: [];
        $logPath = storage_path('logs/laravel.log');
        $ukuranLog = is_file($logPath) ? (int) filesize($logPath) : 0;
        $free = @disk_free_space(base_path());

        $semesterAktif = $this->periode->aktif();

        return [
            'versi_aplikasi' => (string) config('app.version', '1.0.0'),
            'laravel' => app()->version(),
            'php' => PHP_VERSION,
            'database' => $dbOk ? 'Terhubung' : 'Gagal: '.$dbPesan,
            'database_ok' => $dbOk,
            'migrasi_terakhir' => $migrasiTerakhir?->migration ?? null,
            'batch_terakhir' => $migrasiTerakhir?->batch ?? null,
            'semester_aktif' => $this->periode->label($semesterAktif),
            'storage_writable' => is_writable(storage_path()) && is_writable(storage_path('framework/views')),
            'view_cached' => count($viewFiles),
            'log_size' => $this->formatUkuran($ukuranLog),
            'log_size_byte' => $ukuranLog,
            'disk_free' => $free === false ? null : $this->formatUkuran($free),
            'session_driver' => (string) config('session.driver'),
            'cache_store' => (string) config('cache.default'),
        ];
    }

    /**
     * Tindakan pembaruan yang tersedia. Urutan penting:
     * migrasi -> bersihkan cache -> bangun ulang cache view.
     *
     * @return array<string, array<string, mixed>>
     */
    public function opsi(): array
    {
        return [
            'migrasi' => [
                'label' => 'Jalankan migrasi database',
                'deskripsi' => 'Menerapkan perubahan struktur database yang belum berjalan.',
                'perintah' => 'migrate',
                'parameter' => ['--force' => true],
            ],
            'optimize_clear' => [
                'label' => 'Bersihkan cache aplikasi',
                'deskripsi' => 'Menghapus cache config, route, view, dan events.',
                'perintah' => 'optimize:clear',
                'parameter' => [],
            ],
            'view_cache' => [
                'label' => 'Bangun ulang cache tampilan',
                'deskripsi' => 'Meng-compile seluruh Blade agar halaman lebih cepat.',
                'perintah' => 'view:cache',
                'parameter' => [],
            ],
            'storage_link' => [
                'label' => 'Buat tautan storage',
                'deskripsi' => 'Membuat symlink public/storage bila belum tersedia.',
                'perintah' => 'storage:link',
                'parameter' => [],
            ],
        ];
    }

    /**
     * @param  array<int, string>  $tindakan
     * @return array<int, array<string, mixed>>
     */
    public function jalankan(array $tindakan, Pengguna $pengguna): array
    {
        $dipilih = array_fill_keys($tindakan, true);
        $hasil = [];

        foreach ($this->opsi() as $kunci => $opsi) {
            if (! isset($dipilih[$kunci])) {
                continue;
            }

            $mulai = microtime(true);
            $status = 'berhasil';
            $keterangan = $opsi['label'];
            $keluaran = '';

            try {
                $exit = Artisan::call($opsi['perintah'], $opsi['parameter']);
                $keluaran = Artisan::output();

                if ($exit !== 0) {
                    $status = 'gagal';
                    $keterangan = $opsi['label'].' (kode keluar '.$exit.')';
                }
            } catch (Throwable $e) {
                $status = 'gagal';
                $keluaran = $e->getMessage();
                $keterangan = $e->getMessage();
            }

            $durasi = (int) round((microtime(true) - $mulai) * 1000);

            RiwayatPembaruan::create([
                'id_pengguna' => $pengguna->id_pengguna,
                'jenis_tindakan' => $kunci,
                'label_tindakan' => $opsi['label'],
                'status' => $status,
                'keterangan' => Str::limit($keterangan, 255),
                'keluaran' => $keluaran !== '' ? Str::limit($keluaran, 60000) : null,
                'durasi_ms' => $durasi,
                'dibuat_pada' => now(),
            ]);

            $hasil[] = [
                'jenis' => $kunci,
                'label' => $opsi['label'],
                'status' => $status,
                'keterangan' => $keterangan,
                'keluaran' => $keluaran,
                'durasi_ms' => $durasi,
            ];
        }

        return $hasil;
    }

    /**
     * Ambil ringkasan error terakhir dari laravel.log.
     *
     * @return array<int, array{waktu: string, pesan: string, detail: array<int, string>}>
     */
    public function logErrorTerbaru(int $batas = 20): array
    {
        $path = storage_path('logs/laravel.log');

        if (! is_file($path)) {
            return [];
        }

        $baris = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if (! $baris) {
            return [];
        }

        $baris = array_slice($baris, -4000);

        $entri = [];
        $terkini = null;

        foreach ($baris as $line) {
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] \w+\.ERROR: (.*)$/', $line, $m)) {
                if ($terkini !== null) {
                    $entri[] = $terkini;
                }

                $terkini = [
                    'waktu' => $m[1],
                    'pesan' => $m[2],
                    'detail' => [],
                ];

                continue;
            }

            if ($terkini !== null && count($terkini['detail']) < 10) {
                $terkini['detail'][] = $line;
            }
        }

        if ($terkini !== null) {
            $entri[] = $terkini;
        }

        return array_slice(array_reverse($entri), 0, $batas);
    }

    protected function formatUkuran(int $bytes): string
    {
        $satuan = ['B', 'KB', 'MB', 'GB', 'TB'];
        $indeks = 0;
        $nilai = (float) $bytes;

        while ($nilai >= 1024 && $indeks < count($satuan) - 1) {
            $nilai /= 1024;
            $indeks++;
        }

        return round($nilai, $indeks === 0 ? 0 : 1).' '.$satuan[$indeks];
    }
}