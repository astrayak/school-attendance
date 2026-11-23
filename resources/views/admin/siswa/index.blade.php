@extends('layouts.app')

@section('judul', 'Data Siswa')
@section('deskripsi', 'Kelola identitas, akun, kelas, dan QR Code siswa.')

@section('aksi')
    <a href="{{ route('admin.siswa.create') }}" class="btn-primary btn-sm">
        <i data-lucide="user-plus" class="h-4 w-4"></i>
        Tambah Siswa
    </a>
@endsection

@section('konten')
<div class="card mb-6">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.siswa.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_220px_180px_auto]">
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-ink-muted">
                    <i data-lucide="search" class="h-4 w-4"></i>
                </span>
                <input type="search" name="q" value="{{ $pencarian }}" class="form-input pl-9" placeholder="Cari nama, NIS, NISN, atau email...">
            </div>
            <select name="kelas" class="form-select">
                <option value="">Semua kelas</option>
                @foreach ($kelas as $item)
                    <option value="{{ $item->id_kelas }}" @selected((string) $idKelas === (string) $item->id_kelas)>{{ $item->nama_kelas }}</option>
                @endforeach
            </select>
            <select name="status" class="form-select">
                <option value="">Semua status</option>
                @foreach (['aktif' => 'Aktif', 'lulus' => 'Lulus', 'pindah' => 'Pindah', 'nonaktif' => 'Nonaktif'] as $nilai => $label)
                    <option value="{{ $nilai }}" @selected($status === $nilai)>{{ $label }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <button type="submit" class="btn-secondary">Filter</button>
                <a href="{{ route('admin.siswa.index') }}" class="btn-ghost">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="card-header">
        <div>
            <p class="text-sm font-semibold text-ink">Daftar siswa</p>
            <p class="mt-0.5 text-xs text-ink-muted">{{ $siswa->total() }} siswa ditemukan</p>
        </div>
    </div>

    @if ($siswa->isEmpty())
        <x-empty judul="Belum ada siswa" pesan="Tambahkan siswa pertama atau ubah filter pencarian." ikon="users">
            <a href="{{ route('admin.siswa.create') }}" class="btn-primary btn-sm">Tambah siswa</a>
        </x-empty>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-line">
                <thead class="table-head">
                    <tr>
                        <th class="px-5 py-3">Siswa</th>
                        <th class="px-4 py-3">Kelas</th>
                        <th class="px-4 py-3">Orang tua/wali</th>
                        <th class="px-4 py-3">Akun</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line bg-surface">
                    @foreach ($siswa as $murid)
                        @php($kelasSaatIni = $murid->riwayatKelasAktif->first()?->kelas)
                        <tr class="hover:bg-paper/70">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <x-avatar :pengguna="$murid->pengguna" :nama="$murid->nama_lengkap" ukuran="sm" />
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.siswa.show', $murid) }}" class="block truncate text-sm font-semibold text-ink hover:text-moss">{{ $murid->nama_lengkap }}</a>
                                        <p class="text-xs text-ink-muted">NIS {{ $murid->nis }}{{ $murid->nisn ? ' · NISN '.$murid->nisn : '' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="table-cell">{{ $kelasSaatIni?->nama_kelas ?? '—' }}</td>
                            <td class="table-cell">
                                <p class="text-sm">{{ $murid->nama_orang_tua ?: '—' }}</p>
                                <p class="text-xs text-ink-muted">{{ $murid->nomor_telepon_orang_tua ?: '' }}</p>
                            </td>
                            <td class="table-cell">
                                <p class="text-sm">{{ $murid->pengguna?->email }}</p>
                                <x-badge :teks="$murid->pengguna?->status_aktif ? 'Aktif' : 'Nonaktif'"
                                    :warna="$murid->pengguna?->status_aktif ? 'bg-moss-soft text-moss ring-moss/20' : 'bg-paper-deep text-ink-soft ring-ink-muted/20'" />
                            </td>
                            <td class="table-cell">
                                <x-badge :teks="ucfirst($murid->status_siswa)"
                                    :warna="$murid->status_siswa === 'aktif' ? 'bg-moss-soft text-moss ring-moss/20' : 'bg-paper-deep text-ink-soft ring-ink-muted/20'" />
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('admin.siswa.show', $murid) }}" class="btn-ghost btn-sm" title="Lihat">
                                        <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                                    </a>
                                    <a href="{{ route('admin.siswa.edit', $murid) }}" class="btn-ghost btn-sm" title="Ubah">
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
            {{ $siswa->links() }}
        </div>
    @endif
</div>
@endsection
