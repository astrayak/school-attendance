@extends('layouts.app')

@section('judul', 'Semester')
@section('deskripsi', 'Kelola semester pada setiap tahun ajaran.')

@section('aksi')
    <a href="{{ route('admin.semester.create') }}" class="btn-primary btn-sm">
        <i data-lucide="plus" class="h-4 w-4"></i> Tambah Semester
    </a>
@endsection

@section('konten')
<div class="card mb-6">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.semester.index') }}" class="grid gap-3 sm:grid-cols-[1fr_auto] lg:max-w-xl">
            <select name="tahun" class="form-select">
                <option value="">Semua tahun ajaran</option>
                @foreach ($tahunAjaran as $tahun)
                    <option value="{{ $tahun->id_tahun_ajaran }}" @selected((string) $idTahun === (string) $tahun->id_tahun_ajaran)>{{ $tahun->nama_tahun_ajaran }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <button type="submit" class="btn-secondary">Filter</button>
                <a href="{{ route('admin.semester.index') }}" class="btn-ghost">Reset</a>
            </div>
        </form>
    </div>
</div>

@if ($semesterDitandaiAktif && $periodeBerjalan && $semesterDitandaiAktif->id_semester !== $periodeBerjalan->id_semester)
    <div class="mb-6 rounded-sm border border-slateblue-soft bg-slateblue-soft px-4 py-3 text-sm text-slateblue">
        Semester <strong>{{ ucfirst($semesterDitandaiAktif->nama_semester) }} {{ $semesterDitandaiAktif->tahunAjaran?->nama_tahun_ajaran }}</strong>
        ditandai aktif, tetapi periode berjalan saat ini adalah
        <strong>{{ ucfirst($periodeBerjalan->nama_semester) }} {{ $periodeBerjalan->tahunAjaran?->nama_tahun_ajaran }}</strong>.
        Jadwal, rekap, dan riwayat mengikuti periode berjalan.
    </div>
@endif

<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
    @forelse ($semester as $item)
        <div class="card flex flex-col">
            <div class="card-header">
                <div>
                    <p class="text-sm font-semibold text-ink">Semester {{ ucfirst($item->nama_semester) }}</p>
                    <p class="mt-0.5 text-xs text-ink-muted">{{ $item->tahunAjaran?->nama_tahun_ajaran }}</p>
                </div>
                <x-badge :teks="$item->status_aktif ? 'Aktif' : 'Nonaktif'" :warna="$item->status_aktif ? 'bg-moss-soft text-moss ring-moss/20' : 'bg-paper-deep text-ink-soft ring-ink-muted/20'" />
            </div>
            <div class="card-body flex-1 space-y-3">
                <div class="flex items-center gap-2 text-sm text-ink-soft">
                    <i data-lucide="calendar" class="h-4 w-4 text-ink-muted"></i>
                    {{ $item->tanggal_mulai?->format('d/m/Y') }} – {{ $item->tanggal_selesai?->format('d/m/Y') }}
                </div>
                <div class="grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-sm bg-paper px-2 py-2">
                        <p class="text-lg font-semibold text-ink">{{ $item->penugasan_guru_count }}</p>
                        <p class="text-[11px] text-ink-muted">Penugasan</p>
                    </div>
                    <div class="rounded-sm bg-paper px-2 py-2">
                        <p class="text-lg font-semibold text-ink">{{ $item->versi_jadwal_count }}</p>
                        <p class="text-[11px] text-ink-muted">Versi jadwal</p>
                    </div>
                    <div class="rounded-sm bg-paper px-2 py-2">
                        <p class="text-lg font-semibold text-ink">{{ $item->jadwal_piket_count }}</p>
                        <p class="text-[11px] text-ink-muted">Jadwal piket</p>
                    </div>
                </div>
            </div>
            <div class="border-t border-line px-5 py-3 text-right">
                <a href="{{ route('admin.semester.edit', $item) }}" class="btn-ghost btn-sm">
                    <i data-lucide="pencil" class="h-3.5 w-3.5"></i> Ubah
                </a>
            </div>
        </div>
    @empty
        <div class="card md:col-span-2 xl:col-span-3">
            <x-empty judul="Belum ada semester" pesan="Tambahkan semester pada tahun ajaran yang sudah dibuat." ikon="calendar-clock">
                <a href="{{ route('admin.semester.create') }}" class="btn-primary btn-sm">Tambah semester</a>
            </x-empty>
        </div>
    @endforelse
</div>

@if ($semester->hasPages())
    <div class="mt-6">{{ $semester->links() }}</div>
@endif
@endsection
