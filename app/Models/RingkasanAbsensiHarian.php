<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RingkasanAbsensiHarian extends ModelDasar
{
    public const CREATED_AT = null;

    public const UPDATED_AT = 'diperbarui_pada';

    protected $table = 'ringkasan_absensi_harian';

    protected $primaryKey = 'id_ringkasan_harian';

    protected $fillable = [
        'id_siswa',
        'id_kelas',
        'tanggal',
        'jumlah_mapel_dijadwalkan',
        'jumlah_hadir',
        'jumlah_izin',
        'jumlah_sakit',
        'jumlah_alpha',
        'jumlah_belum_diisi',
        'status_harian',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'diperbarui_pada' => 'datetime',
        ];
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'id_kelas', 'id_kelas');
    }
}
