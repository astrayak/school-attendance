<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TahunAjaran extends ModelDasar
{
    protected $table = 'tahun_ajaran';

    protected $primaryKey = 'id_tahun_ajaran';

    protected $fillable = [
        'nama_tahun_ajaran',
        'tanggal_mulai',
        'tanggal_selesai',
        'status_aktif',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'status_aktif' => 'boolean',
            'dibuat_pada' => 'datetime',
            'diperbarui_pada' => 'datetime',
        ];
    }

    public function semester(): HasMany
    {
        return $this->hasMany(Semester::class, 'id_tahun_ajaran', 'id_tahun_ajaran');
    }

    public function kelas(): HasMany
    {
        return $this->hasMany(Kelas::class, 'id_tahun_ajaran', 'id_tahun_ajaran');
    }

    public function riwayatKelas(): HasMany
    {
        return $this->hasMany(RiwayatKelasSiswa::class, 'id_tahun_ajaran', 'id_tahun_ajaran');
    }
}
