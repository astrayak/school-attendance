<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatatanAbsensi extends ModelDasar
{
    public const HADIR = 'hadir';

    public const IZIN = 'izin';

    public const SAKIT = 'sakit';

    public const ALPHA = 'alpha';

    public const METODE_MANUAL = 'manual';

    public const METODE_QR = 'qr_code';

    protected $table = 'catatan_absensi';

    protected $primaryKey = 'id_catatan_absensi';

    protected $fillable = [
        'id_sesi_absensi',
        'id_siswa',
        'status_kehadiran',
        'metode_absensi',
        'waktu_kehadiran',
        'id_pengguna_pencatat',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'waktu_kehadiran' => 'datetime',
            'dibuat_pada' => 'datetime',
            'diperbarui_pada' => 'datetime',
        ];
    }

    public function scopeHadir(Builder $query): Builder
    {
        return $query->where('status_kehadiran', self::HADIR);
    }

    public function sesiAbsensi(): BelongsTo
    {
        return $this->belongsTo(SesiAbsensi::class, 'id_sesi_absensi', 'id_sesi_absensi');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa');
    }

    public function pencatat(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna_pencatat', 'id_pengguna');
    }

    public function audit(): HasMany
    {
        return $this->hasMany(AuditAbsensi::class, 'id_catatan_absensi', 'id_catatan_absensi');
    }
}
