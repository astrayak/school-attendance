@extends('layouts.app')

@section('judul', 'Mata Pelajaran')
@section('deskripsi', 'Kelola daftar mata pelajaran dan status aktifnya.')

@section('aksi')
    <a href="{{ route('admin.mata-pelajaran.create') }}" class="btn-primary btn-sm">
        <i data-lucide="plus" class="h-4 w-4"></i> Tambah Mapel
    </a>
@endsection

@section('konten')
<div class="card mb-6">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.mata-pelajaran.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_180px_auto]">
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-ink-muted">
                    <i data-lucide="search" class="h-4 w-4"></i>
                </span>
                <input type="search" name="q" value="{{ $pencarian }}" class="form-input pl-9" placeholder="Cari kode atau nama mata pelajaran...">
            </div>
            <select name="status" class="form-select">
                <option value="">Semua status</option>
                <option value="aktif" @selected($status === 'aktif')>Aktif</option>
                <option value="nonaktif" @selected($status === 'nonaktif')>Nonaktif</option>
            </select>
            <div class="flex gap-2">
                <button type="submit" class="btn-secondary">Filter</button>
                <a href="{{ route('admin.mata-pelajaran.index') }}" class="btn-ghost">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="card-header">
        <div>
            <p class="text-sm font-semibold text-ink">Daftar mata pelajaran</p>
            <p class="mt-0.5 text-xs text-ink-muted">{{ $mataPelajaran->total() }} data ditemukan</p>
        </div>
    </div>

    @if ($mataPelajaran->isEmpty())
        <x-empty judul="Belum ada mata pelajaran" pesan="Tambahkan mata pelajaran untuk menyusun penugasan guru." ikon="book-open">
            <a href="{{ route('admin.mata-pelajaran.create') }}" class="btn-primary btn-sm">Tambah mapel</a>
        </x-empty>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-line">
                <thead class="table-head">
                    <tr>
                        <th class="px-5 py-3">Kode</th>
                        <th class="px-4 py-3">Mata pelajaran</th>
                        <th class="px-4 py-3 text-center">Penugasan</th>
                        <th class="px-4 py-3 text-center">Jadwal</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line bg-surface">
                    @foreach ($mataPelajaran as $item)
                        <tr class="hover:bg-paper/70">
                            <td class="px-5 py-3">
                                <span class="inline-flex rounded-sm bg-paper-deep px-2 py-1 text-xs font-semibold text-ink-soft">{{ $item->kode_mata_pelajaran }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-sm font-semibold text-ink">{{ $item->nama_mata_pelajaran }}</p>
                                <p class="max-w-md truncate text-xs text-ink-muted">{{ $item->deskripsi ?: 'Tanpa deskripsi' }}</p>
                            </td>
                            <td class="table-cell text-center">{{ $item->penugasan_guru_count }}</td>
                            <td class="table-cell text-center">{{ $item->jadwal_count }}</td>
                            <td class="px-4 py-3">
                                <x-badge :teks="$item->status_aktif ? 'Aktif' : 'Nonaktif'" :warna="$item->status_aktif ? 'bg-moss-soft text-moss ring-moss/20' : 'bg-paper-deep text-ink-soft ring-ink-muted/20'" />
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.mata-pelajaran.edit', $item) }}" class="btn-ghost btn-sm" title="Ubah">
                                    <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="border-t border-line px-5 py-3">{{ $mataPelajaran->links() }}</div>
    @endif
</div>
@endsection
