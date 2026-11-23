@php($semester = $semester ?? null)

<div class="card">
    <div class="card-header">
        <div>
            <p class="text-sm font-semibold text-ink">Informasi Semester</p>
            <p class="mt-0.5 text-xs text-ink-muted">Rentang semester harus berada di dalam tahun ajaran.</p>
        </div>
    </div>
    <div class="card-body space-y-4">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="id_tahun_ajaran" class="form-label">Tahun ajaran <span class="text-clay">*</span></label>
                <select id="id_tahun_ajaran" name="id_tahun_ajaran" class="form-select" required>
                    <option value="">Pilih tahun ajaran</option>
                    @foreach ($tahunAjaran as $tahun)
                        <option value="{{ $tahun->id_tahun_ajaran }}" @selected((string) old('id_tahun_ajaran', $semester?->id_tahun_ajaran) === (string) $tahun->id_tahun_ajaran)>{{ $tahun->nama_tahun_ajaran }}</option>
                    @endforeach
                </select>
                @error('id_tahun_ajaran') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="nama_semester" class="form-label">Nama semester <span class="text-clay">*</span></label>
                <select id="nama_semester" name="nama_semester" class="form-select" required>
                    <option value="">Pilih semester</option>
                    <option value="ganjil" @selected(old('nama_semester', $semester?->nama_semester) === 'ganjil')>Ganjil</option>
                    <option value="genap" @selected(old('nama_semester', $semester?->nama_semester) === 'genap')>Genap</option>
                </select>
                @error('nama_semester') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="tanggal_mulai" class="form-label">Tanggal mulai <span class="text-clay">*</span></label>
                <input type="date" id="tanggal_mulai" name="tanggal_mulai" value="{{ old('tanggal_mulai', $semester?->tanggal_mulai?->format('Y-m-d')) }}" class="form-input" required>
                @error('tanggal_mulai') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="tanggal_selesai" class="form-label">Tanggal selesai <span class="text-clay">*</span></label>
                <input type="date" id="tanggal_selesai" name="tanggal_selesai" value="{{ old('tanggal_selesai', $semester?->tanggal_selesai?->format('Y-m-d')) }}" class="form-input" required>
                @error('tanggal_selesai') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        </div>
        <label class="flex items-start gap-3 rounded-sm border border-line px-4 py-3">
            <input type="checkbox" name="status_aktif" value="1" class="mt-0.5 h-4 w-4 rounded border-line-strong text-moss focus:ring-moss" @checked(old('status_aktif', $semester?->status_aktif ?? false))>
            <span>
                <span class="block text-sm font-medium text-ink">Jadikan semester aktif</span>
                <span class="block text-xs text-ink-muted">Tahun ajaran terkait juga akan diaktifkan, dan semester/tahun ajaran lain dinonaktifkan.</span>
            </span>
        </label>
    </div>
</div>

<div class="mt-6 flex flex-wrap items-center justify-end gap-3">
    <a href="{{ route('admin.semester.index') }}" class="btn-secondary">Batal</a>
    <button type="submit" class="btn-primary">
        <i data-lucide="save" class="h-4 w-4"></i>
        {{ $semester ? 'Simpan Perubahan' : 'Simpan Semester' }}
    </button>
</div>
