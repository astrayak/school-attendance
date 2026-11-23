<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SesiAbsensi extends ModelDasar
{
    public const STATUS_MENUNGGU = 'menunggu';

    public const STATUS_AKTIF = 'aktif';

    public const STATUS_MENUNGGU_KONFIRMASI = 'menunggu_konfirmasi';

    public const STATUS_TERKONFIRMASI = 'terkonfirmasi';

    public const STATUS_PERLU_PEMERIKSAAN = 'perlu_pemeriksaan';

    public const STATUS_BELUM_DIKONFIRMASI = 'belum_dikonfirmasi';

    public const STATUS_DITUTUP = 'ditutup';

    public const STATUS_DIBATALKAN = 'dibatalkan';

    public const METODE_MANUAL = 'manual';

    public const METODE_QR = 'qr_code';

    public const METODE_GABUNGAN = 'gabungan';

    public const PELAKSANA_PENGAMPU = 'guru_pengampu';

    public const PELAKSANA_PIKET = 'guru_piket';

    protected $table = 'sesi_absensi';

    protected $primaryKey = 'id_sesi_absensi';

    protected $fillable = [
        'id_jadwal',
        'tanggal_pelajaran',
        'waktu_mulai',
        'waktu_selesai',
        'waktu_absensi_dibuka',
        'waktu_absensi_ditutup',
        'waktu_batas_konfirmasi',
        'id_guru_penanggung_jawab',
        'id_guru_pelaksana',
        'jenis_pelaksana',
        'metode_absensi',
        'status_sesi',
        'waktu_disimpan',
        'diambil_alih_pada',
        'alasan_pengambilalihan',
        'alasan_pembatalan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_pelajaran' => 'date',
            'waktu_mulai' => 'datetime',
            'waktu_selesai' => 'datetime',
            'waktu_absensi_dibuka' => 'datetime',
            'waktu_absensi_ditutup' => 'datetime',
            'waktu_batas_konfirmasi' => 'datetime',
            'waktu_disimpan' => 'datetime',
            'diambil_alih_pada' => 'datetime',
            'dibuat_pada' => 'datetime',
            'diperbarui_pada' => 'datetime',
        ];
    }

    public function scopeTanggal(Builder $query, string $tanggal): Builder
    {
        return $query->whereDate('tanggal_pelajaran', $tanggal);
    }

    public function jadwal(): BelongsTo
    {
        return $this->belongsTo(JadwalPelajaran::class, 'id_jadwal', 'id_jadwal');
    }

    public function guruPenanggungJawab(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'id_guru_penanggung_jawab', 'id_guru');
    }

    public function guruPelaksana(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'id_guru_pelaksana', 'id_guru');
    }

    public function catatanAbsensi(): HasMany
    {
        return $this->hasMany(CatatanAbsensi::class, 'id_sesi_absensi', 'id_sesi_absensi');
    }

    public function permintaanKonfirmasi(): HasMany
    {
        return $this->hasMany(PermintaanKonfirmasi::class, 'id_sesi_absensi', 'id_sesi_absensi');
    }

    public function sudahDisimpan(): bool
    {
        return $this->waktu_disimpan !== null;
    }

    public function sedangDibuka(): bool
    {
        return in_array($this->status_sesi, [self::STATUS_AKTIF, self::STATUS_MENUNGGU_KONFIRMASI], true)
            && now()->between($this->waktu_absensi_dibuka, $this->waktu_absensi_ditutup);
    }
}
