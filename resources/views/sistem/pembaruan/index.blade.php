@extends('layouts.app')

@section('judul', 'Pembaruan Sistem')
@section('deskripsi', 'Periksa kondisi aplikasi dan jalankan pembaruan database/cache dari satu tempat.')

@section('aksi')
    <a href="{{ route('sistem.troubleshooting.index') }}" class="btn-secondary btn-sm">
        <i data-lucide="wrench" class="h-4 w-4"></i> Riwayat Troubleshooting
    </a>
@endsection

@section('konten')
@if (! $bisaMenjalankan)
    <div class="mb-6 rounded-sm border border-ochre-soft bg-ochre-soft px-4 py-3 text-sm text-ochre">
        Anda dapat melihat status dan riwayat pembaruan, tetapi hanya administrator yang boleh menjalankan tindakan pembaruan.
    </div>
@endif

<div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
    <div class="card p-4">
        <p class="kicker">Versi aplikasi</p>
        <p class="mt-3 font-serif text-2xl font-semibold leading-none text-ink">{{ $pemeriksaan['versi_aplikasi'] }}</p>
        <p class="mt-2 text-xs text-ink-muted">Laravel {{ $pemeriksaan['laravel'] }} · PHP {{ $pemeriksaan['php'] }}</p>
    </div>
    <div class="card p-4">
        <p class="kicker">Database</p>
        <p class="mt-3 text-sm font-semibold text-ink">{{ $pemeriksaan['database'] }}</p>
        <p class="mt-2 text-xs text-ink-muted">
            Migrasi terakhir: {{ $pemeriksaan['migrasi_terakhir'] ?? 'belum ada' }}
            @if ($pemeriksaan['batch_terakhir'])
                · batch {{ $pemeriksaan['batch_terakhir'] }}
            @endif
        </p>
    </div>
    <div class="card p-4">
        <p class="kicker">Semester aktif</p>
        <p class="mt-3 text-sm font-semibold text-ink">{{ $pemeriksaan['semester_aktif'] }}</p>
        <p class="mt-2 text-xs text-ink-muted">Jadwal guru &amp; rekap mengikuti periode ini.</p>
    </div>
    <div class="card p-4">
        <p class="kicker">Storage &amp; tampilan</p>
        <p class="mt-3 text-sm font-semibold {{ $pemeriksaan['storage_writable'] ? 'text-moss' : 'text-clay' }}">
            {{ $pemeriksaan['storage_writable'] ? 'Writable' : 'Tidak writable' }}
        </p>
        <p class="mt-2 text-xs text-ink-muted">{{ $pemeriksaan['view_cached'] }} view ter-compile.</p>
    </div>
    <div class="card p-4">
        <p class="kicker">Log aplikasi</p>
        <p class="mt-3 text-sm font-semibold text-ink">{{ $pemeriksaan['log_size'] }}</p>
        <p class="mt-2 text-xs text-ink-muted">File: storage/logs/laravel.log</p>
    </div>
    <div class="card p-4">
        <p class="kicker">Ruang disk</p>
        <p class="mt-3 text-sm font-semibold text-ink">{{ $pemeriksaan['disk_free'] ?? 'Tidak diketahui' }}</p>
        <p class="mt-2 text-xs text-ink-muted">Sisa ruang pada direktori aplikasi.</p>
    </div>
</div>

<div class="grid gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
        <div class="card">
            <div class="card-header">
                <div>
                    <p class="text-sm font-semibold text-ink">Tindakan pembaruan</p>
                    <p class="mt-0.5 text-xs text-ink-muted">Jalankan berurutan bila perlu: migrasi → bersihkan cache → bangun ulang cache view.</p>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('sistem.pembaruan.jalankan') }}" class="space-y-4">
                    @csrf
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($opsi as $kunci => $item)
                            <label class="flex cursor-pointer items-start gap-3 rounded-sm border border-line bg-paper/60 p-3 transition hover:border-line-strong">
                                <input
                                    type="checkbox"
                                    name="tindakan[]"
                                    value="{{ $kunci }}"
                                    @checked(false)
                                    @disabled(! $bisaMenjalankan)
                                    class="mt-0.5 h-4 w-4"
                                >
                                <span>
                                    <span class="block text-sm font-semibold text-ink">{{ $item['label'] }}</span>
                                    <span class="mt-0.5 block text-xs text-ink-muted">{{ $item['deskripsi'] }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-line pt-4">
                        <p class="mr-auto text-xs text-ink-muted">
                            Pembaruan tidak mengubah source code aplikasi. Untuk update kode, deploy terlebih dahulu lalu jalankan migrasi &amp; cache di sini.
                        </p>
                        <button type="submit" class="btn-primary" @disabled(! $bisaMenjalankan)>
                            <i data-lucide="refresh-cw" class="h-4 w-4"></i> Jalankan Pembaruan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="card-header">
                <div>
                    <p class="text-sm font-semibold text-ink">Riwayat pembaruan terakhir</p>
                    <p class="mt-0.5 text-xs text-ink-muted">8 tindakan terakhir yang dijalankan di aplikasi ini.</p>
                </div>
                <a href="{{ route('sistem.troubleshooting.index') }}" class="btn-ghost btn-sm">Lihat semua</a>
            </div>

            @if ($riwayat->isEmpty())
                <x-empty judul="Belum ada riwayat" pesan="Belum ada pembaruan yang dijalankan." ikon="history" />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-line">
                        <thead class="table-head">
                            <tr>
                                <th class="px-5 py-3">Waktu</th>
                                <th class="px-4 py-3">Tindakan</th>
                                <th class="px-4 py-3">Pengguna</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-5 py-3">Durasi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line bg-surface">
                            @foreach ($riwayat as $item)
                                <tr class="hover:bg-paper/70">
                                    <td class="whitespace-nowrap px-5 py-3 text-sm text-ink-soft">
                                        {{ $item->dibuat_pada?->format('d/m/Y') }}
                                        <span class="block text-xs text-ink-muted">{{ $item->dibuat_pada?->format('H:i:s') }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="text-sm font-medium text-ink">{{ $item->label_tindakan }}</p>
                                        <p class="text-xs text-ink-muted">{{ $item->jenis_tindakan }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-ink-soft">{{ $item->pengguna?->nama_pengguna ?? 'Sistem' }}</td>
                                    <td class="px-4 py-3">
                                        @php($warnaStatus = $item->status === 'berhasil' ? 'bg-moss-soft text-moss ring-moss/20' : 'bg-clay-soft text-clay ring-clay/20')
                                        <span class="inline-flex rounded-sm px-2 py-1 text-xs font-semibold ring-1 ring-inset {{ $warnaStatus }}">{{ ucfirst($item->status) }}</span>
                                    </td>
                                    <td class="px-5 py-3 text-sm text-ink-soft">{{ $item->durasi_ms !== null ? $item->durasi_ms.' ms' : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="space-y-6">
        <div class="card">
            <div class="card-header">
                <p class="text-sm font-semibold text-ink">Konfigurasi</p>
            </div>
            <div class="card-body space-y-2 text-xs text-ink-soft">
                <p class="flex justify-between gap-3"><span>Driver sesi</span><strong class="font-mono text-ink">{{ $pemeriksaan['session_driver'] }}</strong></p>
                <p class="flex justify-between gap-3"><span>Cache</span><strong class="font-mono text-ink">{{ $pemeriksaan['cache_store'] }}</strong></p>
                <p class="flex justify-between gap-3"><span>Laravel</span><strong class="font-mono text-ink">{{ $pemeriksaan['laravel'] }}</strong></p>
                <p class="flex justify-between gap-3"><span>PHP</span><strong class="font-mono text-ink">{{ $pemeriksaan['php'] }}</strong></p>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <p class="text-sm font-semibold text-ink">Catatan</p>
            </div>
            <div class="card-body space-y-3 text-xs text-ink-muted">
                <p><strong class="text-ink-soft">Migrasi database</strong> menambah/mengubah tabel. Jalankan setelah update source code.</p>
                <p><strong class="text-ink-soft">Bersihkan cache</strong> menghapus cache lama agar perubahan konfigurasi/view terbaca.</p>
                <p><strong class="text-ink-soft">Bangun ulang cache view</strong> mempercepat render Blade.</p>
                <p class="rounded-sm bg-ochre-soft px-3 py-2 text-ochre ring-1 ring-inset ring-ochre-soft">
                    Hanya administrator yang dapat menjalankan tindakan. Setiap tindakan dicatat di riwayat troubleshooting.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection