@extends('layouts.app')

@section('judul', 'Tetapkan Petugas Konfirmasi')
@section('deskripsi', 'Pilih siswa dan tentukan urutan prioritas penerima permintaan konfirmasi.')

@section('aksi')
    <a href="{{ route('admin.petugas-konfirmasi.index') }}" class="btn-ghost btn-sm"><i data-lucide="arrow-left" class="h-4 w-4"></i> Kembali</a>
@endsection

@section('konten')
<div class="card mb-6">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.petugas-konfirmasi.create') }}" class="grid gap-3 sm:grid-cols-[1fr_1fr_auto]">
            <select name="semester" class="form-select">
                <option value="">Pilih semester</option>
                @foreach ($semester as $item)
                    <option value="{{ $item->id_semester }}" @selected((string) $idSemester === (string) $item->id_semester)>{{ ucfirst($item->nama_semester) }} · {{ $item->tahunAjaran?->nama_tahun_ajaran }}</option>
                @endforeach
            </select>
            <select name="kelas" class="form-select">
                <option value="">Pilih kelas</option>
                @foreach ($kelas as $item)
                    <option value="{{ $item->id_kelas }}" @selected((string) $idKelas === (string) $item->id_kelas)>{{ $item->nama_kelas }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn-secondary">Tampilkan Siswa</button>
        </form>
    </div>
</div>

@if ($idKelas && $idSemester)
    @if ($siswa->isEmpty())
        <div class="card">
            <x-empty judul="Tidak ada siswa aktif" pesan="Kelas ini belum memiliki siswa aktif pada tanggal hari ini." ikon="users" />
        </div>
    @else
        <form method="POST" action="{{ route('admin.petugas-konfirmasi.store') }}">
            @csrf
            <input type="hidden" name="id_kelas" value="{{ $idKelas }}">
            <input type="hidden" name="id_semester" value="{{ $idSemester }}">

            <div class="card overflow-hidden">
                <div class="card-header">
                    <div>
                        <p class="text-sm font-semibold text-ink">Pilih petugas & prioritas</p>
                        <p class="mt-0.5 text-xs text-ink-muted">Isi angka prioritas (1 = pertama dihubungi). Kosongkan bila tidak dipilih.</p>
                    </div>
                    <x-badge :teks="$siswa->count().' siswa'" warna="bg-paper-deep text-ink-soft ring-ink-muted/20" />
                </div>
                <div class="divide-y divide-line">
                    @foreach ($siswa as $murid)
                        @php($terdaftarItem = $terdaftar->get($murid->id_siswa))
                        <div class="flex flex-wrap items-center gap-4 px-5 py-3">
                            <span class="grid h-9 w-9 shrink-0 place-items-center border border-moss/20 bg-moss-soft font-serif text-[13px] font-semibold text-moss">
                                {{ mb_strtoupper(mb_substr($murid->nama_lengkap, 0, 1)) }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-ink">{{ $murid->nama_lengkap }}</p>
                                <p class="text-xs text-ink-muted">NIS {{ $murid->nis }}</p>
                            </div>
                            @if ($terdaftarItem)
                                <div class="flex items-center gap-2 text-xs">
                                    <x-badge :teks="$terdaftarItem->status_aktif ? 'Sudah terdaftar' : 'Terdaftar nonaktif'"
                                        :warna="$terdaftarItem->status_aktif ? 'bg-moss-soft text-moss ring-moss/20' : 'bg-paper-deep text-ink-soft ring-ink-muted/20'" />
                                    <span class="text-ink-muted">Prioritas {{ $terdaftarItem->urutan_prioritas }}</span>
                                </div>
                            @endif
                            <div class="w-28">
                                <label for="urutan-{{ $murid->id_siswa }}" class="sr-only">Prioritas {{ $murid->nama_lengkap }}</label>
                                <input type="number" id="urutan-{{ $murid->id_siswa }}" name="urutan[{{ $murid->id_siswa }}]" value="{{ old('urutan.'.$murid->id_siswa, $terdaftarItem?->urutan_prioritas) }}" class="form-input" min="1" max="99" placeholder="Prioritas">
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="border-t border-line px-5 py-3">
                    @error('urutan') <p class="mb-2 text-sm text-clay">{{ $message }}</p> @enderror
                    @error('urutan.*') <p class="mb-2 text-sm text-clay">{{ $message }}</p> @enderror
                    <div class="flex justify-end gap-3">
                        <a href="{{ route('admin.petugas-konfirmasi.index') }}" class="btn-secondary">Batal</a>
                        <button type="submit" class="btn-primary"><i data-lucide="save" class="h-4 w-4"></i> Simpan Petugas</button>
                    </div>
                </div>
            </div>
        </form>
    @endif
@else
    <div class="card">
        <x-empty judul="Pilih kelas dan semester" pesan="Pilih semester serta kelas untuk menampilkan daftar siswa yang dapat ditetapkan sebagai petugas konfirmasi." ikon="user-check" />
    </div>
@endif
@endsection
