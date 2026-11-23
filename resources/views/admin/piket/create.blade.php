@extends('layouts.app')

@section('judul', 'Tambah Jadwal Piket')
@section('deskripsi', 'Tetapkan guru piket pada hari dan periode tertentu.')

@section('aksi')
    <a href="{{ route('admin.piket.index') }}" class="btn-ghost btn-sm"><i data-lucide="arrow-left" class="h-4 w-4"></i> Kembali</a>
@endsection

@section('konten')
<form method="POST" action="{{ route('admin.piket.store') }}">
    @csrf
    <div class="card">
        <div class="card-header">
            <div>
                <p class="text-sm font-semibold text-ink">Detail Piket</p>
                <p class="mt-0.5 text-xs text-ink-muted">Satu guru tidak dapat memiliki dua piket pada hari yang sama dalam rentang yang bertumpang tindih.</p>
            </div>
        </div>
        <div class="card-body grid gap-4 sm:grid-cols-2">
            <div>
                <label for="id_semester" class="form-label">Semester <span class="text-clay">*</span></label>
                <select id="id_semester" name="id_semester" class="form-select" required>
                    <option value="">Pilih semester</option>
                    @foreach ($semester as $item)
                        <option value="{{ $item->id_semester }}" @selected((string) old('id_semester', $terpilih['id_semester']) === (string) $item->id_semester)>{{ ucfirst($item->nama_semester) }} · {{ $item->tahunAjaran?->nama_tahun_ajaran }}</option>
                    @endforeach
                </select>
                @error('id_semester') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="id_guru" class="form-label">Guru <span class="text-clay">*</span></label>
                <select id="id_guru" name="id_guru" class="form-select" required>
                    <option value="">Pilih guru</option>
                    @foreach ($guru as $item)
                        <option value="{{ $item->id_guru }}" @selected((string) old('id_guru', $terpilih['id_guru']) === (string) $item->id_guru)>{{ $item->nama_lengkap }}</option>
                    @endforeach
                </select>
                @error('id_guru') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="hari" class="form-label">Hari <span class="text-clay">*</span></label>
                <select id="hari" name="hari" class="form-select" required>
                    <option value="">Pilih hari</option>
                    @foreach (\App\Support\Status::HARI as $nilai => $label)
                        <option value="{{ $nilai }}" @selected(old('hari') === $nilai)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('hari') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="tanggal_mulai_berlaku" class="form-label">Mulai berlaku <span class="text-clay">*</span></label>
                <input type="date" id="tanggal_mulai_berlaku" name="tanggal_mulai_berlaku" value="{{ old('tanggal_mulai_berlaku', now()->toDateString()) }}" class="form-input" required>
                @error('tanggal_mulai_berlaku') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="tanggal_berakhir" class="form-label">Tanggal berakhir</label>
                <input type="date" id="tanggal_berakhir" name="tanggal_berakhir" value="{{ old('tanggal_berakhir') }}" class="form-input">
                <p class="form-help">Kosongkan bila berlaku sampai semester berakhir.</p>
                @error('tanggal_berakhir') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    <div class="mt-6 flex justify-end gap-3">
        <a href="{{ route('admin.piket.index') }}" class="btn-secondary">Batal</a>
        <button type="submit" class="btn-primary"><i data-lucide="save" class="h-4 w-4"></i> Simpan Piket</button>
    </div>
</form>
@endsection
