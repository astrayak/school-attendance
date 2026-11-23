@extends('layouts.app')

@section('judul', 'Scanner QR')
@section('deskripsi', 'Pindai QR Code siswa pada sesi yang jendela absensinya sedang terbuka.')

@section('aksi')
    <a href="{{ route('guru.sesi.index') }}" class="btn-ghost btn-sm">Sesi Hari Ini</a>
@endsection

@section('konten')
<div class="mb-6 flex flex-col gap-3 rounded-sm border border-slateblue-soft bg-slateblue-soft px-4 py-3 text-sm text-slateblue sm:flex-row sm:items-center">
    <i data-lucide="camera" class="h-5 w-5 shrink-0"></i>
    <p class="flex-1">Kamera browser hanya aktif pada konteks aman. Gunakan <code class="rounded bg-surface/70 px-1.5 py-0.5 text-xs font-semibold">http://127.0.0.1:8000</code> atau HTTPS, lalu izinkan akses kamera saat diminta.</p>
</div>

<div class="mb-6 grid gap-4 sm:grid-cols-3">
    <x-stat-card label="Sesi Hari Ini" :nilai="$sesi->count()" ikon="calendar-days" warna="slate" />
    <x-stat-card label="Siap Dipindai" :nilai="$terbuka->count()" ikon="scan-line" warna="emerald" keterangan="Jendela absensi terbuka" />
    <x-stat-card label="Sudah Disimpan" :nilai="$sesi->whereNotNull('waktu_disimpan')->count()" ikon="check-circle-2" warna="violet" />
</div>

<div class="card overflow-hidden">
    <div class="card-header">
        <div>
            <p class="text-sm font-semibold text-ink">Sesi yang dapat dipindai</p>
            <p class="mt-0.5 text-xs text-ink-muted">Hanya sesi dengan jendela absensi terbuka yang menampilkan tombol scanner.</p>
        </div>
        <span class="inline-flex items-center gap-2 border border-line bg-surface px-2.5 py-1 font-mono text-[10px] uppercase tracking-[0.08em] text-ink-soft">
            <span class="h-1.5 w-1.5 bg-moss"></span>
            {{ now()->format('H:i') }} WITA
        </span>
    </div>

    @if ($sesi->isEmpty())
        <x-empty judul="Tidak ada sesi hari ini" pesan="Sesi dibuat otomatis dari jadwal aktif. Pastikan jadwal semester berjalan sudah diatur admin." ikon="calendar-x" />
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-line">
                <thead class="table-head">
                    <tr>
                        <th class="px-5 py-3">Waktu</th>
                        <th class="px-4 py-3">Kelas & mapel</th>
                        <th class="px-4 py-3">Guru pelaksana</th>
                        <th class="px-4 py-3">Jendela absensi</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line bg-surface">
                    @foreach ($sesi as $item)
                        @php($siap = $terbuka->contains('id_sesi_absensi', $item->id_sesi_absensi))
                        <tr class="{{ $siap ? 'bg-moss-soft/30' : '' }} hover:bg-paper/70">
                            <td class="px-5 py-3 text-sm text-ink-soft">
                                <p class="font-medium text-ink">{{ $item->waktu_mulai?->format('H:i') }} – {{ $item->waktu_selesai?->format('H:i') }}</p>
                                <p class="text-xs text-ink-muted">{{ $item->tanggal_pelajaran?->format('d/m/Y') }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-sm font-semibold text-ink">{{ $item->jadwal?->mataPelajaran?->nama_mata_pelajaran ?? '—' }}</p>
                                <p class="text-xs text-ink-muted">{{ $item->jadwal?->kelas?->nama_kelas }}</p>
                            </td>
                            <td class="table-cell">
                                <p>{{ $item->guruPelaksana?->nama_lengkap ?? $item->guruPenanggungJawab?->nama_lengkap ?? '—' }}</p>
                                <p class="text-xs text-ink-muted">{{ \App\Support\Status::label('JENIS_PELAKSANA', $item->jenis_pelaksana) }}</p>
                            </td>
                            <td class="table-cell">
                                {{ $item->waktu_absensi_dibuka?->format('H:i') }} – {{ $item->waktu_absensi_ditutup?->format('H:i') }}
                                @if ($siap)
                                    <span class="mt-1 block text-[11px] font-medium text-moss">Sedang terbuka</span>
                                @else
                                    <span class="mt-1 block text-[11px] text-ink-muted">Di luar jendela</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <x-status :status="$item->status_sesi" kelompok="SESI" />
                                @if ($item->waktu_disimpan)
                                    <span class="mt-1 block text-[11px] text-ink-muted">Disimpan {{ $item->waktu_disimpan->format('H:i') }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                @if ($siap)
                                    <a href="{{ route('guru.scanner.show', $item) }}" class="btn-primary btn-sm"><i data-lucide="scan-line" class="h-3.5 w-3.5"></i> Pindai</a>
                                @else
                                    <a href="{{ route('guru.sesi.show', $item) }}" class="btn-secondary btn-sm">Detail</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
