<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JadwalGuruPiket extends ModelDasar
{
    protected $table = 'jadwal_guru_piket';

    protected $primaryKey = 'id_jadwal_piket';

    protected $fillable = [
        'id_guru',
        'hari',
        'tanggal_mulai_berlaku',
        'tanggal_berakhir',
        'id_semester',
        'status_aktif',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai_berlaku' => 'date',
            'tanggal_berakhir' => 'date',
            'status_aktif' => 'boolean',
            'dibuat_pada' => 'datetime',
            'diperbarui_pada' => 'datetime',
        ];
    }

    public function scopeBerlakuPada(Builder $query, Carbon $tanggal): Builder
    {
        return $query->where('status_aktif', true)
            ->where('hari', $this->namaHari($tanggal))
            ->whereDate('tanggal_mulai_berlaku', '<=', $tanggal->toDateString())
            ->where(function (Builder $q) use ($tanggal) {
                $q->whereNull('tanggal_berakhir')
                    ->orWhereDate('tanggal_berakhir', '>=', $tanggal->toDateString());
            });
    }

    public function namaHari(Carbon $tanggal): string
    {
        return strtolower($tanggal->locale('id')->translatedFormat('l'));
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'id_guru', 'id_guru');
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'id_semester', 'id_semester');
    }
}
