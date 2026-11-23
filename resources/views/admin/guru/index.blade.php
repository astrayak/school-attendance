@extends('layouts.app')

@section('judul', 'Data Guru')
@section('deskripsi', 'Kelola akun, identitas, penugasan, dan jadwal guru.')

@section('aksi')
    <a href="{{ route('admin.guru.create') }}" class="btn-primary btn-sm">
        <i data-lucide="user-plus" class="h-4 w-4"></i> Tambah Guru
    </a>
@endsection

@section('konten')
<div class="card mb-6">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.guru.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_180px_auto]">
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-ink-muted">
                    <i data-lucide="search" class="h-4 w-4"></i>
                </span>
                <input type="search" name="q" value="{{ $pencarian }}" class="form-input pl-9" placeholder="Cari nama, NIP, NUPTK, atau email...">
            </div>
            <select name="status" class="form-select">
                <option value="">Semua status</option>
                <option value="aktif" @selected($status === 'aktif')>Aktif</option>
                <option value="nonaktif" @selected($status === 'nonaktif')>Nonaktif</option>
            </select>
            <div class="flex gap-2">
                <button type="submit" class="btn-secondary">Filter</button>
                <a href="{{ route('admin.guru.index') }}" class="btn-ghost">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="card-header">
        <div>
            <p class="text-sm font-semibold text-ink">Daftar guru</p>
            <p class="mt-0.5 text-xs text-ink-muted">{{ $guru->total() }} guru ditemukan</p>
        </div>
    </div>

    @if ($guru->isEmpty())
        <x-empty judul="Belum ada guru" pesan="Tambahkan guru pertama atau ubah filter pencarian." ikon="graduation-cap">
            <a href="{{ route('admin.guru.create') }}" class="btn-primary btn-sm">Tambah guru</a>
        </x-empty>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-line">
                <thead class="table-head">
                    <tr>
                        <th class="px-5 py-3">Guru</th>
                        <th class="px-4 py-3">Kontak</th>
                        <th class="px-4 py-3 text-center">Penugasan</th>
                        <th class="px-4 py-3 text-center">Jadwal</th>
                        <th class="px-4 py-3 text-center">Piket</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line bg-surface">
                    @foreach ($guru as $item)
                        <tr class="hover:bg-paper/70">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <x-avatar :pengguna="$item->pengguna" :nama="$item->nama_lengkap" ukuran="sm" />
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.guru.show', $item) }}" class="block truncate text-sm font-semibold text-ink hover:text-moss">{{ $item->nama_lengkap }}</a>
                                        <p class="text-xs text-ink-muted">NIP {{ $item->nip ?: '—' }}{{ $item->nuptk ? ' · NUPTK '.$item->nuptk : '' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="table-cell">
                                <p class="text-sm">{{ $item->pengguna?->email }}</p>
                                <p class="text-xs text-ink-muted">{{ $item->nomor_telepon ?: '—' }}</p>
                            </td>
                            <td class="table-cell text-center">{{ $item->penugasan_count }}</td>
                            <td class="table-cell text-center">{{ $item->jadwal_count }}</td>
                            <td class="table-cell text-center">{{ $item->jadwal_piket_count }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-col items-start gap-1">
                                    <x-badge :teks="ucfirst($item->status_guru)" :warna="$item->status_guru === 'aktif' ? 'bg-moss-soft text-moss ring-moss/20' : 'bg-paper-deep text-ink-soft ring-ink-muted/20'" />
                                    <x-badge :teks="$item->pengguna?->status_aktif ? 'Akun aktif' : 'Akun nonaktif'" :warna="$item->pengguna?->status_aktif ? 'bg-moss-soft text-moss ring-moss/20' : 'bg-clay-soft text-clay ring-clay/20'" />
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('admin.guru.show', $item) }}" class="btn-ghost btn-sm" title="Lihat">
                                        <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                                    </a>
                                    <a href="{{ route('admin.guru.edit', $item) }}" class="btn-ghost btn-sm" title="Ubah">
                                        <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="border-t border-line px-5 py-3">
            {{ $guru->links() }}
        </div>
    @endif
</div>
@endsection
