<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notifikasi extends ModelDasar
{
    public const CREATED_AT = 'dibuat_pada';

    public const UPDATED_AT = null;

    protected $table = 'notifikasi';

    protected $primaryKey = 'id_notifikasi';

    protected $fillable = [
        'id_pengguna',
        'judul',
        'pesan',
        'jenis',
        'url',
        'sudah_dibaca',
        'dibaca_pada',
    ];

    protected function casts(): array
    {
        return [
            'sudah_dibaca' => 'boolean',
            'dibaca_pada' => 'datetime',
            'dibuat_pada' => 'datetime',
        ];
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id_pengguna');
    }
}
