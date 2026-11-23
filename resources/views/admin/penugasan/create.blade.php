@extends('layouts.app')

@section('judul', 'Tambah Penugasan')
@section('deskripsi', 'Tetapkan guru pengampu untuk satu mata pelajaran dan kelas.')

@section('aksi')
    <a href="{{ route('admin.penugasan.index') }}" class="btn-ghost btn-sm"><i data-lucide="arrow-left" class="h-4 w-4"></i> Kembali</a>
@endsection

@section('konten')
<form method="POST" action="{{ route('admin.penugasan.store') }}">
    @csrf
    <div class="grid gap-6 xl:grid-cols-3">
        <div class="card xl:col-span-2">
            <div class="card-header">
                <div>
                    <p class="text-sm font-semibold text-ink">Detail Penugasan</p>
                    <p class="mt-0.5 text-xs text-ink-muted">Guru hanya dapat dipilih pada jadwal bila penugasan aktif.</p>
                </div>
            </div>
            <div class="card-body grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="id_semester" class="form-label">Semester <span class="text-clay">*</span></label>
                    <select id="id_semester" name="id_semester" class="form-select" required>
                        <option value="">Pilih semester</option>
                        @foreach ($semester as $item)
                            <option value="{{ $item->id_semester }}" @selected((string) old('id_semester', $terpilih['id_semester']) === (string) $item->id_semester)>
                                {{ ucfirst($item->nama_semester) }} · {{ $item->tahunAjaran?->nama_tahun_ajaran }}
                            </option>
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
                    <label for="id_mata_pelajaran" class="form-label">Mata pelajaran <span class="text-clay">*</span></label>
                    <select id="id_mata_pelajaran" name="id_mata_pelajaran" class="form-select" required>
                        <option value="">Pilih mata pelajaran</option>
                        @foreach ($mataPelajaran as $item)
                            <option value="{{ $item->id_mata_pelajaran }}" @selected((string) old('id_mata_pelajaran') === (string) $item->id_mata_pelajaran)>{{ $item->nama_mata_pelajaran }}</option>
                        @endforeach
                    </select>
                    @error('id_mata_pelajaran') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="id_kelas" class="form-label">Kelas <span class="text-clay">*</span></label>
                    <select id="id_kelas" name="id_kelas" class="form-select" required>
                        <option value="">Pilih kelas</option>
                        @foreach ($kelas as $item)
                            <option value="{{ $item->id_kelas }}" @selected((string) old('id_kelas', $terpilih['id_kelas']) === (string) $item->id_kelas)>{{ $item->nama_kelas }} · {{ $item->tahunAjaran?->nama_tahun_ajaran }}</option>
                        @endforeach
                    </select>
                    <p class="form-help">Kelas harus berasal dari tahun ajaran semester yang dipilih.</p>
                    @error('id_kelas') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="tanggal_mulai" class="form-label">Tanggal mulai <span class="text-clay">*</span></label>
                    <input type="date" id="tanggal_mulai" name="tanggal_mulai" value="{{ old('tanggal_mulai', now()->toDateString()) }}" class="form-input" required>
                    @error('tanggal_mulai') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="tanggal_selesai" class="form-label">Tanggal selesai</label>
                    <input type="date" id="tanggal_selesai" name="tanggal_selesai" value="{{ old('tanggal_selesai') }}" class="form-input">
                    <p class="form-help">Kosongkan bila penugasan berlaku sampai akhir semester.</p>
                    @error('tanggal_selesai') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="card h-fit">
            <div class="card-header">
                <p class="text-sm font-semibold text-ink">Catatan</p>
            </div>
            <div class="card-body space-y-3 text-sm text-ink-soft">
                <p>Penugasan yang sudah ada akan diaktifkan kembali dengan periode yang diperbarui, bukan diduplikasi.</p>
                <p>Setelah penugasan tersedia, admin dapat menambahkan jadwal pelajaran pada versi jadwal semester terkait.</p>
                <p>Tanggal mulai dan selesai wajib berada di dalam rentang semester yang dipilih.</p>
                <p class="rounded-sm bg-ochre-soft px-3 py-2 text-xs text-ochre ring-1 ring-inset ring-ochre-soft">
                    Pastikan kelas dan semester berada pada tahun ajaran yang sama.
                </p>
            </div>
        </div>
    </div>

    <div class="mt-6 flex justify-end gap-3">
        <a href="{{ route('admin.penugasan.index') }}" class="btn-secondary">Batal</a>
        <button type="submit" class="btn-primary"><i data-lucide="save" class="h-4 w-4"></i> Simpan Penugasan</button>
    </div>
</form>
@endsection
