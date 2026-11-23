@extends('layouts.app')

@section('judul', 'Guru Piket')
@section('deskripsi', 'Atur guru yang berjaga untuk mengambil alih sesi yang terlewat.')

@section('aksi')
    <a href="{{ route('admin.piket.create', array_filter(['semester' => $idSemester, 'guru' => $idGuru])) }}" class="btn-primary btn-sm">
        <i data-lucide="plus" class="h-4 w-4"></i> Tambah Piket
    </a>
@endsection

@section('konten')
<div class="card mb-6">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.piket.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <select name="semester" class="form-select">
                <option value="">Semua semester</option>
                @foreach ($semester as $item)
                    <option value="{{ $item->id_semester }}" @selected((string) $idSemester === (string) $item->id_semester)>{{ ucfirst($item->nama_semester) }} · {{ $item->tahunAjaran?->nama_tahun_ajaran }}</option>
                @endforeach
            </select>
            <select name="guru" class="form-select">
                <option value="">Semua guru</option>
                @foreach ($guru as $item)
                    <option value="{{ $item->id_guru }}" @selected((string) $idGuru === (string) $item->id_guru)>{{ $item->nama_lengkap }}</option>
                @endforeach
            </select>
            <select name="hari" class="form-select">
                <option value="">Semua hari</option>
                @foreach (\App\Support\Status::HARI as $nilai => $label)
                    <option value="{{ $nilai }}" @selected($hari === $nilai)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="status" class="form-select">
                <option value="">Semua status</option>
                <option value="aktif" @selected($status === 'aktif')>Aktif</option>
                <option value="nonaktif" @selected($status === 'nonaktif')>Nonaktif</option>
            </select>
            <div class="flex gap-2">
                <button type="submit" class="btn-secondary">Filter</button>
                <a href="{{ route('admin.piket.index') }}" class="btn-ghost">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="card-header">
        <div>
            <p class="text-sm font-semibold text-ink">Jadwal piket</p>
            <p class="mt-0.5 text-xs text-ink-muted">{{ $piket->total() }} jadwal ditemukan</p>
        </div>
    </div>

    @if ($piket->isEmpty())
        <x-empty judul="Belum ada jadwal piket" pesan="Tetapkan guru piket agar sesi yang terlewat dapat diambil alih." ikon="shield-check" />
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-line">
                <thead class="table-head">
                    <tr>
                        <th class="px-5 py-3">Guru</th>
                        <th class="px-4 py-3">Hari</th>
                        <th class="px-4 py-3">Semester</th>
                        <th class="px-4 py-3">Berlaku</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line bg-surface">
                    @foreach ($piket as $item)
                        <tr class="hover:bg-paper/70">
                            <td class="px-5 py-3">
                                <a href="{{ route('admin.guru.show', $item->guru) }}" class="text-sm font-semibold text-ink hover:text-moss">{{ $item->guru?->nama_lengkap ?? '—' }}</a>
                                <p class="text-xs text-ink-muted">{{ $item->guru?->nip ? 'NIP '.$item->guru->nip : '' }}</p>
                            </td>
                            <td class="table-cell capitalize">{{ $item->hari }}</td>
                            <td class="table-cell">{{ ucfirst($item->semester?->nama_semester ?? '—') }} · {{ $item->semester?->tahunAjaran?->nama_tahun_ajaran }}</td>
                            <td class="table-cell">
                                {{ $item->tanggal_mulai_berlaku?->format('d/m/Y') }} – {{ $item->tanggal_berakhir?->format('d/m/Y') ?? 'sekarang' }}
                            </td>
                            <td class="px-4 py-3">
                                <x-badge :teks="$item->status_aktif ? 'Aktif' : 'Nonaktif'" :warna="$item->status_aktif ? 'bg-moss-soft text-moss ring-moss/20' : 'bg-paper-deep text-ink-soft ring-ink-muted/20'" />
                            </td>
                            <td class="px-4 py-3 text-right">
                                <form method="POST" action="{{ route('admin.piket.status', $item) }}">
                                    @csrf
                                    <button type="submit" class="btn-ghost btn-sm {{ $item->status_aktif ? 'text-clay hover:bg-clay-soft' : 'text-moss hover:bg-moss-soft' }}">
                                        {{ $item->status_aktif ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="border-t border-line px-5 py-3">{{ $piket->links() }}</div>
    @endif
</div>
@endsection
