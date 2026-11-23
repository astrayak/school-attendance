<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogAktivitas extends ModelDasar
{
    public const CREATED_AT = 'dibuat_pada';

    public const UPDATED_AT = null;

    protected $table = 'log_aktivitas';

    protected $primaryKey = 'id_log_aktivitas';

    protected $fillable = [
        'id_pengguna',
        'jenis_aktivitas',
        'nama_modul',
        'deskripsi_aktivitas',
        'jenis_data',
        'id_data_terkait',
        'data_sebelumnya',
        'data_sesudahnya',
        'alamat_ip',
        'waktu_aktivitas',
    ];

    protected function casts(): array
    {
        return [
            'data_sebelumnya' => 'array',
            'data_sesudahnya' => 'array',
            'waktu_aktivitas' => 'datetime',
            'dibuat_pada' => 'datetime',
        ];
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id_pengguna');
    }
}
