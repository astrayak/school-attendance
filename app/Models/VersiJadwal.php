<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VersiJadwal extends ModelDasar
{
    public const STATUS_DRAF = 'draf';

    public const STATUS_AKTIF = 'aktif';

    public const STATUS_ARSIP = 'arsip';

    protected $table = 'versi_jadwal';

    protected $primaryKey = 'id_versi_jadwal';

    protected $fillable = [
        'id_semester',
        'nama_versi',
        'tanggal_berlaku',
        'tanggal_berakhir',
        'status_versi',
        'dibuat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_berlaku' => 'date',
            'tanggal_berakhir' => 'date',
            'dibuat_pada' => 'datetime',
            'diperbarui_pada' => 'datetime',
        ];
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'id_semester', 'id_semester');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'dibuat_oleh', 'id_pengguna');
    }

    public function jadwal(): HasMany
    {
        return $this->hasMany(JadwalPelajaran::class, 'id_versi_jadwal', 'id_versi_jadwal');
    }
}
