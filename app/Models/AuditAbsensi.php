<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditAbsensi extends ModelDasar
{
    public const CREATED_AT = 'dibuat_pada';

    public const UPDATED_AT = null;

    protected $table = 'audit_absensi';

    protected $primaryKey = 'id_audit_absensi';

    protected $fillable = [
        'id_catatan_absensi',
        'id_pengguna',
        'status_sebelumnya',
        'status_sesudahnya',
        'alasan_perubahan',
        'waktu_perubahan',
    ];

    protected function casts(): array
    {
        return [
            'waktu_perubahan' => 'datetime',
            'dibuat_pada' => 'datetime',
        ];
    }

    public function catatanAbsensi(): BelongsTo
    {
        return $this->belongsTo(CatatanAbsensi::class, 'id_catatan_absensi', 'id_catatan_absensi');
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id_pengguna');
    }
}
