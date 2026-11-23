<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatPembaruan extends Model
{
    public const CREATED_AT = 'dibuat_pada';

    public const UPDATED_AT = null;

    protected $table = 'riwayat_pembaruan';

    protected $primaryKey = 'id_riwayat_pembaruan';

    protected $fillable = [
        'id_pengguna',
        'jenis_tindakan',
        'label_tindakan',
        'status',
        'keterangan',
        'keluaran',
        'durasi_ms',
        'dibuat_pada',
    ];

    protected function casts(): array
    {
        return [
            'durasi_ms' => 'integer',
            'dibuat_pada' => 'datetime',
        ];
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id_pengguna');
    }
}