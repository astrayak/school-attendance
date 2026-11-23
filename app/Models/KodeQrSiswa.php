<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KodeQrSiswa extends ModelDasar
{
    protected $table = 'kode_qr_siswa';

    protected $primaryKey = 'id_kode_qr';

    protected $fillable = [
        'id_siswa',
        'token_hash',
        'token_terenkripsi',
        'status_aktif',
        'tanggal_dibuat',
        'tanggal_dicabut',
    ];

    protected $hidden = [
        'token_hash',
        'token_terenkripsi',
    ];

    protected function casts(): array
    {
        return [
            'status_aktif' => 'boolean',
            'tanggal_dibuat' => 'datetime',
            'tanggal_dicabut' => 'datetime',
            'dibuat_pada' => 'datetime',
            'diperbarui_pada' => 'datetime',
        ];
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status_aktif', true);
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa');
    }
}
