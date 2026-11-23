<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MataPelajaran extends ModelDasar
{
    protected $table = 'mata_pelajaran';

    protected $primaryKey = 'id_mata_pelajaran';

    protected $fillable = [
        'kode_mata_pelajaran',
        'nama_mata_pelajaran',
        'deskripsi',
        'status_aktif',
    ];

    protected function casts(): array
    {
        return [
            'status_aktif' => 'boolean',
            'dibuat_pada' => 'datetime',
            'diperbarui_pada' => 'datetime',
        ];
    }

    public function penugasanGuru(): HasMany
    {
        return $this->hasMany(PenugasanGuru::class, 'id_mata_pelajaran', 'id_mata_pelajaran');
    }

    public function jadwal(): HasMany
    {
        return $this->hasMany(JadwalPelajaran::class, 'id_mata_pelajaran', 'id_mata_pelajaran');
    }
}
