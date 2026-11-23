@php($tahunAjaran = $tahunAjaran ?? null)

<div class="card">
    <div class="card-header">
        <div>
            <p class="text-sm font-semibold text-ink">Informasi Tahun Ajaran</p>
            <p class="mt-0.5 text-xs text-ink-muted">Hanya satu tahun ajaran yang dapat berstatus aktif.</p>
        </div>
    </div>
    <div class="card-body space-y-4">
        <div>
            <label for="nama_tahun_ajaran" class="form-label">Nama tahun ajaran <span class="text-clay">*</span></label>
            <input type="text" id="nama_tahun_ajaran" name="nama_tahun_ajaran" value="{{ old('nama_tahun_ajaran', $tahunAjaran?->nama_tahun_ajaran) }}" class="form-input" placeholder="Contoh: 2026/2027" required maxlength="20">
            @error('nama_tahun_ajaran') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="tanggal_mulai" class="form-label">Tanggal mulai <span class="text-clay">*</span></label>
                <input type="date" id="tanggal_mulai" name="tanggal_mulai" value="{{ old('tanggal_mulai', $tahunAjaran?->tanggal_mulai?->format('Y-m-d')) }}" class="form-input" required>
                @error('tanggal_mulai') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="tanggal_selesai" class="form-label">Tanggal selesai <span class="text-clay">*</span></label>
                <input type="date" id="tanggal_selesai" name="tanggal_selesai" value="{{ old('tanggal_selesai', $tahunAjaran?->tanggal_selesai?->format('Y-m-d')) }}" class="form-input" required>
                @error('tanggal_selesai') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        </div>
        <label class="flex items-start gap-3 rounded-sm border border-line px-4 py-3">
            <input type="checkbox" name="status_aktif" value="1" class="mt-0.5 h-4 w-4 rounded border-line-strong text-moss focus:ring-moss" @checked(old('status_aktif', $tahunAjaran?->status_aktif ?? false))>
            <span>
                <span class="block text-sm font-medium text-ink">Jadikan tahun ajaran aktif</span>
                <span class="block text-xs text-ink-muted">Tahun ajaran aktif lain akan otomatis dinonaktifkan.</span>
            </span>
        </label>
    </div>
</div>

<div class="mt-6 flex flex-wrap items-center justify-end gap-3">
    <a href="{{ route('admin.tahun-ajaran.index') }}" class="btn-secondary">Batal</a>
    <button type="submit" class="btn-primary">
        <i data-lucide="save" class="h-4 w-4"></i>
        {{ $tahunAjaran ? 'Simpan Perubahan' : 'Simpan Tahun Ajaran' }}
    </button>
</div>
