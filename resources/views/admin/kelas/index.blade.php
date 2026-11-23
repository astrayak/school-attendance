@extends('layouts.app')

@section('judul', 'Data Kelas')
@section('deskripsi', 'Kelola rombongan belajar, wali kelas, dan jumlah siswa aktif.')

@section('aksi')
    <a href="{{ route('admin.kelas.create') }}" class="btn-primary btn-sm">
        <i data-lucide="plus" class="h-4 w-4"></i> Tambah Kelas
    </a>
@endsection

@section('konten')
<div class="card mb-6">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.kelas.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_220px_auto]">
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-ink-muted">
                    <i data-lucide="search" class="h-4 w-4"></i>
                </span>
                <input type="search" name="q" value="{{ $pencarian }}" class="form-input pl-9" placeholder="Cari nama kelas...">
            </div>
            <select name="tahun" class="form-select">
                <option value="">Semua tahun ajaran</option>
                @foreach ($tahunAjaran as $tahun)
                    <option value="{{ $tahun->id_tahun_ajaran }}" @selected((string) $idTahun === (string) $tahun->id_tahun_ajaran)>{{ $tahun->nama_tahun_ajaran }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <button type="submit" class="btn-secondary">Filter</button>
                <a href="{{ route('admin.kelas.index') }}" class="btn-ghost">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="card-header">
        <div>
            <p class="text-sm font-semibold text-ink">Daftar kelas</p>
            <p class="mt-0.5 text-xs text-ink-muted">{{ $kelas->total() }} kelas ditemukan</p>
        </div>
    </div>

    @if ($kelas->isEmpty())
        <x-empty judul="Belum ada kelas" pesan="Tambahkan kelas untuk mulai menyusun jadwal." ikon="school">
            <a href="{{ route('admin.kelas.create') }}" class="btn-primary btn-sm">Tambah kelas</a>
        </x-empty>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-line">
                <thead class="table-head">
                    <tr>
                        <th class="px-5 py-3">Kelas</th>
                        <th class="px-4 py-3">Tahun ajaran</th>
                        <th class="px-4 py-3">Wali kelas</th>
                        <th class="px-4 py-3 text-center">Siswa aktif</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line bg-surface">
                    @foreach ($kelas as $item)
                        <tr class="hover:bg-paper/70">
                            <td class="px-5 py-3">
                                <p class="text-sm font-semibold text-ink">{{ $item->nama_kelas }}</p>
                                <p class="text-xs text-ink-muted">Tingkat {{ $item->tingkat }}{{ $item->jurusan ? ' · '.$item->jurusan : '' }}</p>
                            </td>
                            <td class="table-cell">{{ $item->tahunAjaran?->nama_tahun_ajaran ?? '—' }}</td>
                            <td class="table-cell">{{ $item->waliKelas?->nama_lengkap ?? 'Belum ditentukan' }}</td>
                            <td class="table-cell text-center">{{ $item->jumlah_siswa }}</td>
                            <td class="px-4 py-3">
                                <x-badge :teks="ucfirst($item->status_kelas)" :warna="$item->status_kelas === 'aktif' ? 'bg-moss-soft text-moss ring-moss/20' : 'bg-paper-deep text-ink-soft ring-ink-muted/20'" />
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.kelas.edit', $item) }}" class="btn-ghost btn-sm" title="Ubah">
                                    <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="border-t border-line px-5 py-3">{{ $kelas->links() }}</div>
    @endif
</div>
@endsection
