@extends('layouts.app')

@section('judul', 'QR Code Saya')
@section('deskripsi', 'Tunjukkan QR Code ini kepada guru saat absensi menggunakan scanner.')

@section('aksi')
    <a href="{{ route('siswa.dashboard') }}" class="btn-ghost btn-sm">Kembali</a>
@endsection

@section('konten')
@php
    $svgBersih = preg_replace('/<\?xml.*?\?>\s*/s', '', (string) $svg);
@endphp

<div class="grid gap-6 xl:grid-cols-3">
    <div class="xl:col-span-2">
        <div class="card overflow-hidden">
            <div class="border-l-2 border-l-accent bg-paper px-6 py-5 print:hidden">
                <div>
                    <p class="kicker">Kartu absensi digital</p>
                    <h2 class="mt-1.5 font-serif text-xl font-semibold text-ink">{{ $siswa->nama_lengkap }}</h2>
                    <p class="mt-1 text-[12.5px] text-ink-muted">{{ $kelas?->nama_kelas ?? 'Belum memiliki kelas' }} · NIS {{ $siswa->nis }}</p>
                </div>
            </div>

            <div class="px-6 py-8">
                <div class="mx-auto flex max-w-sm flex-col items-center">
                    <div class="rounded-sm border border-line bg-surface p-4 shadow-none">
                        @if ($svgBersih)
                            <div class="h-64 w-64 [&>svg]:h-full [&>svg]:w-full" role="img" aria-label="QR Code absensi {{ $siswa->nama_lengkap }}">
                                {!! $svgBersih !!}
                            </div>
                        @else
                            <div class="grid h-64 w-64 place-items-center rounded-sm bg-paper text-center text-sm text-ink-muted">
                                QR Code belum tersedia. Muat ulang halaman ini.
                            </div>
                        @endif
                    </div>

                    <p class="mt-5 text-center text-sm font-medium text-ink">{{ $siswa->nama_lengkap }}</p>
                    <p class="mt-0.5 text-center text-xs text-ink-muted">{{ $kelas?->nama_kelas }} · NIS {{ $siswa->nis }}</p>
                    <p class="mt-3 inline-flex items-center gap-2 border border-moss/25 bg-moss-soft px-2.5 py-1 font-mono text-[10px] uppercase tracking-[0.08em] text-moss">
                        <i data-lucide="shield-check" class="h-3.5 w-3.5"></i>
                        Aktif sejak {{ $kode->tanggal_dibuat?->locale('id')->translatedFormat('d F Y') }}
                    </p>

                    <div class="mt-6 flex w-full flex-col gap-2 sm:flex-row print:hidden">
                        <button type="button" class="btn-secondary flex-1" onclick="window.print()">
                            <i data-lucide="printer" class="h-4 w-4"></i> Cetak / Simpan PDF
                        </button>
                        <a href="{{ route('siswa.dashboard') }}" class="btn-ghost flex-1">Ke Dashboard</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="card">
            <div class="card-header">
                <p class="text-sm font-semibold text-ink">Cara menggunakan</p>
            </div>
            <div class="card-body space-y-3 text-sm text-ink-soft">
                <div class="flex gap-3">
                    <span class="grid h-7 w-7 shrink-0 place-items-center rounded-sm bg-moss-soft text-xs font-bold text-moss">1</span>
                    <p>Tunjukkan QR Code ini kepada guru saat sesi pembelajaran berlangsung.</p>
                </div>
                <div class="flex gap-3">
                    <span class="grid h-7 w-7 shrink-0 place-items-center rounded-sm bg-moss-soft text-xs font-bold text-moss">2</span>
                    <p>Guru memindai melalui menu Scanner QR. Kehadiran tercatat otomatis.</p>
                </div>
                <div class="flex gap-3">
                    <span class="grid h-7 w-7 shrink-0 place-items-center rounded-sm bg-moss-soft text-xs font-bold text-moss">3</span>
                    <p>Jika kamera bermasalah, tunjukkan QR agar guru mengetik tokennya secara manual.</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <p class="text-sm font-semibold text-ink">Keamanan</p>
            </div>
            <div class="card-body space-y-3 text-sm text-ink-soft">
                <div class="flex items-start gap-3">
                    <i data-lucide="lock" class="mt-0.5 h-4 w-4 shrink-0 text-moss"></i>
                    <p>QR hanya menyimpan token acak. Tidak ada nama, NIS, atau data pribadi di dalamnya.</p>
                </div>
                <div class="flex items-start gap-3">
                    <i data-lucide="refresh-cw" class="mt-0.5 h-4 w-4 shrink-0 text-moss"></i>
                    <p>Admin dapat mencabut dan membuat ulang QR kapan saja. QR lama otomatis tidak berlaku.</p>
                </div>
                <div class="flex items-start gap-3">
                    <i data-lucide="shield-alert" class="mt-0.5 h-4 w-4 shrink-0 text-ochre"></i>
                    <p>Jangan membagikan QR kepada orang lain. Penyalahgunaan tercatat pada log aktivitas.</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <p class="text-sm font-semibold text-ink">Identitas kartu</p>
            </div>
            <dl class="divide-y divide-line text-sm">
                <div class="flex items-center justify-between px-5 py-3">
                    <dt class="text-ink-muted">Nama</dt>
                    <dd class="text-right font-medium text-ink">{{ $siswa->nama_lengkap }}</dd>
                </div>
                <div class="flex items-center justify-between px-5 py-3">
                    <dt class="text-ink-muted">NIS</dt>
                    <dd class="font-medium text-ink">{{ $siswa->nis }}</dd>
                </div>
                <div class="flex items-center justify-between px-5 py-3">
                    <dt class="text-ink-muted">Kelas</dt>
                    <dd class="font-medium text-ink">{{ $kelas?->nama_kelas ?? '—' }}</dd>
                </div>
            </dl>
        </div>
    </div>
</div>
@endsection
