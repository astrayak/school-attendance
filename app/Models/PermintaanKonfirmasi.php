<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermintaanKonfirmasi extends ModelDasar
{
    public const MENUNGGU = 'menunggu';

    public const DIKONFIRMASI = 'dikonfirmasi';

    public const TIDAK_DIKONFIRMASI = 'tidak_dikonfirmasi';

    public const DIALIHKAN = 'dialihkan';

    public const KEDALUWARSA = 'kedaluwarsa';

    public const BERLANGSUNG = 'pembelajaran_berlangsung';

    public const TIDAK_BERLANGSUNG = 'pembelajaran_tidak_berlangsung';

    protected $table = 'permintaan_konfirmasi';

    protected $primaryKey = 'id_permintaan_konfirmasi';

    protected $fillable = [
        'id_sesi_absensi',
        'id_siswa_penerima',
        'id_petugas_konfirmasi',
        'urutan_penerima',
        'status_permintaan',
        'waktu_dikirim',
        'waktu_direspons',
        'waktu_kedaluwarsa',
        'jawaban',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'waktu_dikirim' => 'datetime',
            'waktu_direspons' => 'datetime',
            'waktu_kedaluwarsa' => 'datetime',
            'dibuat_pada' => 'datetime',
            'diperbarui_pada' => 'datetime',
        ];
    }

    public function scopeMenunggu(Builder $query): Builder
    {
        return $query->where('status_permintaan', self::MENUNGGU);
    }

    public function sesiAbsensi(): BelongsTo
    {
        return $this->belongsTo(SesiAbsensi::class, 'id_sesi_absensi', 'id_sesi_absensi');
    }

    public function siswaPenerima(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'id_siswa_penerima', 'id_siswa');
    }

    public function petugasKonfirmasi(): BelongsTo
    {
        return $this->belongsTo(PetugasKonfirmasi::class, 'id_petugas_konfirmasi', 'id_petugas_konfirmasi');
    }
}
