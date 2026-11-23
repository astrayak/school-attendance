<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengaturanSistem extends ModelDasar
{
    protected $table = 'pengaturan_sistem';

    protected $primaryKey = 'id_pengaturan';

    protected $fillable = [
        'kunci',
        'nilai',
        'tipe',
        'deskripsi',
        'tanggal_berlaku',
        'status_aktif',
        'dibuat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_berlaku' => 'date',
            'status_aktif' => 'boolean',
            'dibuat_pada' => 'datetime',
            'diperbarui_pada' => 'datetime',
        ];
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'dibuat_oleh', 'id_pengguna');
    }
}
