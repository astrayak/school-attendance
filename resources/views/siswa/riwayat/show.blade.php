@extends('layouts.app')

@section('judul', 'Detail Absensi')
@section('deskripsi')
    {{ $catatan->sesiAbsensi?->jadwal?->mataPelajaran?->nama_mata_pelajaran }} · {{ $catatan->sesiAbsensi?->tanggal_pelajaran?->format('d/m/Y') }}
@endsection

@section('aksi')
    <a href="{{ route('siswa.riwayat.index') }}" class="btn-ghost btn-sm">Kembali</a>
@endsection

@section('konten')
<div class="grid gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
        <div class="card overflow-hidden">
            <div class="border-l-2 border-l-accent bg-paper px-6 py-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="kicker">Catatan kehadiran</p>
                        <h2 class="mt-1.5 font-serif text-xl font-semibold text-ink">{{ $catatan->sesiAbsensi?->jadwal?->mataPelajaran?->nama_mata_pelajaran ?? 'Sesi pembelajaran' }}</h2>
                        <p class="mt-1 text-[12.5px] text-ink-muted">
                            {{ $catatan->sesiAbsensi?->jadwal?->kelas?->nama_kelas }} ·
                            {{ $catatan->sesiAbsensi?->tanggal_pelajaran?->locale('id')->translatedFormat('l, d F Y') }} ·
                            {{ $catatan->sesiAbsensi?->waktu_mulai?->format('H:i') }}–{{ $catatan->sesiAbsensi?->waktu_selesai?->format('H:i') }}
                        </p>
                    </div>
                    <x-status :status="$catatan->status_kehadiran" kelompok="KEHADIRAN" />
                </div>
            </div>
            <dl class="grid gap-x-6 gap-y-4 px-6 py-5 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-ink-muted">Nama siswa</dt>
                    <dd class="mt-1 text-sm font-medium text-ink">{{ $siswa->nama_lengkap }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-ink-muted">NIS</dt>
                    <dd class="mt-1 text-sm font-medium text-ink">{{ $siswa->nis }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-ink-muted">Status kehadiran</dt>
                    <dd class="mt-1"><x-status :status="$catatan->status_kehadiran" kelompok="KEHADIRAN" /></dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-ink-muted">Metode pencatatan</dt>
                    <dd class="mt-1 text-sm font-medium text-ink">{{ \App\Support\Status::label('METODE', $catatan->metode_absensi) }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-ink-muted">Waktu kehadiran</dt>
                    <dd class="mt-1 text-sm font-medium text-ink">{{ $catatan->waktu_kehadiran?->format('d/m/Y H:i:s') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-ink-muted">Dicatat oleh</dt>
                    <dd class="mt-1 text-sm font-medium text-ink">{{ $catatan->pencatat?->nama_pengguna ?? 'Sistem' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-medium uppercase tracking-wide text-ink-muted">Keterangan</dt>
                    <dd class="mt-1 text-sm text-ink-soft">{{ $catatan->keterangan ?: 'Tidak ada keterangan.' }}</dd>
                </div>
            </dl>
        </div>

        <div class="card overflow-hidden">
            <div class="card-header">
                <div>
                    <p class="text-sm font-semibold text-ink">Riwayat perubahan</p>
                    <p class="mt-0.5 text-xs text-ink-muted">Jejak audit bila status absensi pernah dikoreksi.</p>
                </div>
            </div>
            @if ($catatan->audit->isEmpty())
                <x-empty judul="Belum ada perubahan" pesan="Status absensi ini tercatat sejak awal tanpa koreksi." ikon="history" />
            @else
                <ol class="divide-y divide-line">
                    @foreach ($catatan->audit->sortByDesc('waktu_perubahan') as $perubahan)
                        <li class="flex gap-3 px-5 py-4">
                            <span class="mt-0.5 grid h-8 w-8 shrink-0 place-items-center border border-line bg-paper-deep text-ink-muted">
                                <i data-lucide="arrow-right-left" class="h-4 w-4"></i>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-ink-soft">
                                    {{ \App\Support\Status::label('KEHADIRAN', $perubahan->status_sebelumnya) }} →
                                    <span class="font-semibold text-ink">{{ \App\Support\Status::label('KEHADIRAN', $perubahan->status_sesudahnya) }}</span>
                                </p>
                                <p class="mt-0.5 text-xs text-ink-muted">
                                    {{ $perubahan->waktu_perubahan?->locale('id')->translatedFormat('d F Y, H:i') }} · {{ $perubahan->pengguna?->nama_pengguna ?? 'Sistem' }}
                                </p>
                                @if ($perubahan->alasan_perubahan)
                                    <p class="mt-1 text-xs text-ink-soft">{{ $perubahan->alasan_perubahan }}</p>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>
    </div>

    <div class="space-y-6">
        <div class="card">
            <div class="card-header">
                <p class="text-sm font-semibold text-ink">Informasi sesi</p>
            </div>
            <dl class="divide-y divide-line text-sm">
                <div class="flex items-center justify-between px-5 py-3">
                    <dt class="text-ink-muted">Tanggal</dt>
                    <dd class="font-medium text-ink">{{ $catatan->sesiAbsensi?->tanggal_pelajaran?->format('d/m/Y') ?? '—' }}</dd>
                </div>
                <div class="flex items-center justify-between px-5 py-3">
                    <dt class="text-ink-muted">Jam pelajaran</dt>
                    <dd class="font-medium text-ink">{{ $catatan->sesiAbsensi?->waktu_mulai?->format('H:i') }}–{{ $catatan->sesiAbsensi?->waktu_selesai?->format('H:i') }}</dd>
                </div>
                <div class="flex items-center justify-between px-5 py-3">
                    <dt class="text-ink-muted">Guru pengampu</dt>
                    <dd class="text-right font-medium text-ink">{{ $catatan->sesiAbsensi?->guruPenanggungJawab?->nama_lengkap ?? '—' }}</dd>
                </div>
                <div class="flex items-center justify-between px-5 py-3">
                    <dt class="text-ink-muted">Guru pelaksana</dt>
                    <dd class="text-right font-medium text-ink">{{ $catatan->sesiAbsensi?->guruPelaksana?->nama_lengkap ?? '—' }}</dd>
                </div>
                <div class="flex items-center justify-between px-5 py-3">
                    <dt class="text-ink-muted">Status sesi</dt>
                    <dd><x-status :status="$catatan->sesiAbsensi?->status_sesi" kelompok="SESI" /></dd>
                </div>
                <div class="flex items-center justify-between px-5 py-3">
                    <dt class="text-ink-muted">Metode sesi</dt>
                    <dd class="font-medium text-ink">{{ \App\Support\Status::label('METODE', $catatan->sesiAbsensi?->metode_absensi) }}</dd>
                </div>
            </dl>
        </div>

        <div class="card">
            <div class="card-header">
                <p class="text-sm font-semibold text-ink">Butuh koreksi?</p>
            </div>
            <div class="card-body text-sm text-ink-soft">
                <p>Jika status kehadiran tidak sesuai, hubungi guru pengampu atau admin. Koreksi akan tercatat pada audit absensi.</p>
                <a href="{{ route('notifikasi.index') }}" class="btn-secondary mt-3 w-full">Buka Notifikasi</a>
            </div>
        </div>
    </div>
</div>
@endsection
