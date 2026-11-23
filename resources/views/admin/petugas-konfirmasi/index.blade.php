@extends('layouts.app')

@section('judul', 'Petugas Konfirmasi')
@section('deskripsi', 'Tunjuk siswa yang memverifikasi bahwa pembelajaran benar-benar berlangsung.')

@section('aksi')
    <a href="{{ route('admin.petugas-konfirmasi.create', array_filter(['semester' => $idSemester, 'kelas' => $idKelas])) }}" class="btn-primary btn-sm">
        <i data-lucide="user-check" class="h-4 w-4"></i> Tetapkan Petugas
    </a>
@endsection

@section('konten')
<div class="card mb-6">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.petugas-konfirmasi.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_180px_auto]">
            <select name="semester" class="form-select">
                <option value="">Semua semester</option>
                @foreach ($semester as $item)
                    <option value="{{ $item->id_semester }}" @selected((string) $idSemester === (string) $item->id_semester)>{{ ucfirst($item->nama_semester) }} · {{ $item->tahunAjaran?->nama_tahun_ajaran }}</option>
                @endforeach
            </select>
            <select name="kelas" class="form-select">
                <option value="">Semua kelas</option>
                @foreach ($kelas as $item)
                    <option value="{{ $item->id_kelas }}" @selected((string) $idKelas === (string) $item->id_kelas)>{{ $item->nama_kelas }}</option>
                @endforeach
            </select>
            <select name="status" class="form-select">
                <option value="">Semua status</option>
                <option value="aktif" @selected($status === 'aktif')>Aktif</option>
                <option value="nonaktif" @selected($status === 'nonaktif')>Nonaktif</option>
            </select>
            <div class="flex gap-2">
                <button type="submit" class="btn-secondary">Filter</button>
                <a href="{{ route('admin.petugas-konfirmasi.index') }}" class="btn-ghost">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="card-header">
        <div>
            <p class="text-sm font-semibold text-ink">Daftar petugas</p>
            <p class="mt-0.5 text-xs text-ink-muted">{{ $petugas->total() }} petugas ditemukan · diurutkan berdasarkan kelas dan prioritas</p>
        </div>
    </div>

    @if ($petugas->isEmpty())
        <x-empty judul="Belum ada petugas konfirmasi" pesan="Tanpa petugas, sesi yang disimpan akan dilaporkan ke admin tanpa konfirmasi siswa." ikon="user-check" />
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-line">
                <thead class="table-head">
                    <tr>
                        <th class="px-5 py-3">Siswa</th>
                        <th class="px-4 py-3">Kelas</th>
                        <th class="px-4 py-3">Semester</th>
                        <th class="px-4 py-3 text-center">Prioritas</th>
                        <th class="px-4 py-3">Periode</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line bg-surface">
                    @foreach ($petugas as $item)
                        <tr class="hover:bg-paper/70">
                            <td class="px-5 py-3">
                                <p class="text-sm font-semibold text-ink">{{ $item->siswa?->nama_lengkap ?? '—' }}</p>
                                <p class="text-xs text-ink-muted">NIS {{ $item->siswa?->nis }}{{ $item->siswa?->pengguna?->email ? ' · '.$item->siswa->pengguna->email : '' }}</p>
                            </td>
                            <td class="table-cell">{{ $item->kelas?->nama_kelas ?? '—' }}</td>
                            <td class="table-cell">{{ ucfirst($item->semester?->nama_semester ?? '—') }} · {{ $item->semester?->tahunAjaran?->nama_tahun_ajaran }}</td>
                            <td class="table-cell text-center">
                                <span class="inline-flex h-7 w-7 items-center justify-center border border-line bg-paper-deep font-mono text-[10px] font-medium text-ink-soft">{{ $item->urutan_prioritas }}</span>
                            </td>
                            <td class="table-cell">{{ $item->tanggal_mulai?->format('d/m/Y') }} – {{ $item->tanggal_selesai?->format('d/m/Y') ?? 'sekarang' }}</td>
                            <td class="px-4 py-3">
                                <x-badge :teks="$item->status_aktif ? 'Aktif' : 'Nonaktif'" :warna="$item->status_aktif ? 'bg-moss-soft text-moss ring-moss/20' : 'bg-paper-deep text-ink-soft ring-ink-muted/20'" />
                            </td>
                            <td class="px-4 py-3 text-right">
                                <form method="POST" action="{{ route('admin.petugas-konfirmasi.status', $item) }}">
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
        <div class="border-t border-line px-5 py-3">{{ $petugas->links() }}</div>
    @endif
</div>
@endsection
