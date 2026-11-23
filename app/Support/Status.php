<?php

namespace App\Support;

class Status
{
    public const PERAN = [
        'admin' => 'Admin',
        'guru' => 'Guru',
        'siswa' => 'Siswa',
    ];

    public const HARI = [
        'senin' => 'Senin',
        'selasa' => 'Selasa',
        'rabu' => 'Rabu',
        'kamis' => 'Kamis',
        'jumat' => 'Jumat',
        'sabtu' => 'Sabtu',
        'minggu' => 'Minggu',
    ];

    public const KEHADIRAN = [
        'hadir' => 'Hadir',
        'izin' => 'Izin',
        'sakit' => 'Sakit',
        'alpha' => 'Alpha',
    ];

    public const METODE = [
        'manual' => 'Manual',
        'qr_code' => 'QR Code',
        'gabungan' => 'Gabungan',
    ];

    public const SESI = [
        'menunggu' => 'Menunggu',
        'aktif' => 'Aktif',
        'menunggu_konfirmasi' => 'Menunggu Konfirmasi',
        'terkonfirmasi' => 'Terkonfirmasi',
        'perlu_pemeriksaan' => 'Perlu Pemeriksaan',
        'belum_dikonfirmasi' => 'Belum Dikonfirmasi',
        'ditutup' => 'Ditutup',
        'dibatalkan' => 'Dibatalkan',
    ];

    public const KONFIRMASI = [
        'menunggu' => 'Menunggu',
        'dikonfirmasi' => 'Dikonfirmasi',
        'tidak_dikonfirmasi' => 'Tidak Dikonfirmasi',
        'dialihkan' => 'Dialihkan',
        'kedaluwarsa' => 'Kedaluwarsa',
    ];

    public const JAWABAN = [
        'pembelajaran_berlangsung' => 'Pembelajaran berlangsung',
        'pembelajaran_tidak_berlangsung' => 'Pembelajaran tidak berlangsung',
    ];

    public const STATUS_HARIAN = [
        'hadir_penuh' => 'Hadir penuh',
        'parsial' => 'Parsial',
        'izin_penuh' => 'Izin penuh',
        'sakit_penuh' => 'Sakit penuh',
        'alpha_penuh' => 'Alpha penuh',
        'campuran_non_hadir' => 'Campuran non-hadir',
        'belum_lengkap' => 'Belum lengkap',
    ];

    public const JENIS_PELAKSANA = [
        'guru_pengampu' => 'Guru Pengampu',
        'guru_piket' => 'Guru Piket',
    ];

    public static function label(string $group, ?string $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return self::{$group}[$value] ?? ucfirst(str_replace('_', ' ', $value));
    }

    public static function badge(?string $status): string
    {
        return match ($status) {
            'hadir', 'terkonfirmasi', 'dikonfirmasi', 'hadir_penuh', 'aktif' => 'bg-moss-soft text-moss ring-moss/25',
            'izin', 'menunggu_konfirmasi', 'menunggu', 'parsial' => 'bg-ochre-soft text-ochre ring-ochre/25',
            'sakit', 'perlu_pemeriksaan', 'campuran_non_hadir' => 'bg-slateblue-soft text-slateblue ring-slateblue/25',
            'alpha', 'alpha_penuh', 'belum_dikonfirmasi', 'tidak_dikonfirmasi', 'kedaluwarsa', 'nonaktif' => 'bg-clay-soft text-clay ring-clay/25',
            'belum_lengkap', 'ditutup', 'dibatalkan' => 'bg-paper-deep text-ink-muted ring-line-strong',
            default => 'bg-paper-deep text-ink-muted ring-line-strong',
        };
    }
}
