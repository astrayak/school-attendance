@extends('layouts.app')

@section('judul', 'Tahun Ajaran')
@section('deskripsi', 'Atur periode akademik dan status aktif sekolah.')

@section('aksi')
    <a href="{{ route('admin.tahun-ajaran.create') }}" class="btn-primary btn-sm">
        <i data-lucide="plus" class="h-4 w-4"></i> Tambah Tahun Ajaran
    </a>
@endsection

@section('konten')
<div class="card overflow-hidden">
    <div class="card-header">
        <div>
            <p class="text-sm font-semibold text-ink">Daftar tahun ajaran</p>
            <p class="mt-0.5 text-xs text-ink-muted">{{ $tahunAjaran->total() }} data ditemukan</p>
        </div>
    </div>

    @if ($tahunAjaran->isEmpty())
        <x-empty judul="Belum ada tahun ajaran" pesan="Tambahkan tahun ajaran sebelum membuat kelas dan semester." ikon="calendar">
            <a href="{{ route('admin.tahun-ajaran.create') }}" class="btn-primary btn-sm">Tambah tahun ajaran</a>
        </x-empty>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-line">
                <thead class="table-head">
                    <tr>
                        <th class="px-5 py-3">Tahun ajaran</th>
                        <th class="px-4 py-3">Periode</th>
                        <th class="px-4 py-3 text-center">Semester</th>
                        <th class="px-4 py-3 text-center">Kelas</th>
                        <th class="px-4 py-3 text-center">Riwayat kelas</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line bg-surface">
                    @foreach ($tahunAjaran as $item)
                        <tr class="hover:bg-paper/70">
                            <td class="px-5 py-3">
                                <p class="text-sm font-semibold text-ink">{{ $item->nama_tahun_ajaran }}</p>
                                @if ($item->status_aktif)
                                    <p class="text-xs text-moss">Tahun ajaran berjalan</p>
                                @endif
                            </td>
                            <td class="table-cell">{{ $item->tanggal_mulai?->format('d/m/Y') }} – {{ $item->tanggal_selesai?->format('d/m/Y') }}</td>
                            <td class="table-cell text-center">{{ $item->semester_count }}</td>
                            <td class="table-cell text-center">{{ $item->kelas_count }}</td>
                            <td class="table-cell text-center">{{ $item->riwayat_kelas_count }}</td>
                            <td class="px-4 py-3">
                                <x-badge :teks="$item->status_aktif ? 'Aktif' : 'Nonaktif'" :warna="$item->status_aktif ? 'bg-moss-soft text-moss ring-moss/20' : 'bg-paper-deep text-ink-soft ring-ink-muted/20'" />
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.tahun-ajaran.edit', $item) }}" class="btn-ghost btn-sm" title="Ubah">
                                    <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="border-t border-line px-5 py-3">{{ $tahunAjaran->links() }}</div>
    @endif
</div>
@endsection
