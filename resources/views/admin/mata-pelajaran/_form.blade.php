@php($mataPelajaran = $mataPelajaran ?? null)

<div class="card">
    <div class="card-header">
        <div>
            <p class="text-sm font-semibold text-ink">Informasi Mata Pelajaran</p>
            <p class="mt-0.5 text-xs text-ink-muted">Kode harus unik agar mudah dirujuk pada jadwal.</p>
        </div>
    </div>
    <div class="card-body space-y-4">
        <div class="grid gap-4 sm:grid-cols-[200px_1fr]">
            <div>
                <label for="kode_mata_pelajaran" class="form-label">Kode <span class="text-clay">*</span></label>
                <input type="text" id="kode_mata_pelajaran" name="kode_mata_pelajaran" value="{{ old('kode_mata_pelajaran', $mataPelajaran?->kode_mata_pelajaran) }}" class="form-input uppercase" placeholder="Contoh: MTK" required maxlength="20">
                @error('kode_mata_pelajaran') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="nama_mata_pelajaran" class="form-label">Nama mata pelajaran <span class="text-clay">*</span></label>
                <input type="text" id="nama_mata_pelajaran" name="nama_mata_pelajaran" value="{{ old('nama_mata_pelajaran', $mataPelajaran?->nama_mata_pelajaran) }}" class="form-input" placeholder="Contoh: Matematika" required maxlength="100">
                @error('nama_mata_pelajaran') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        </div>
        <div>
            <label for="deskripsi" class="form-label">Deskripsi</label>
            <textarea id="deskripsi" name="deskripsi" rows="3" class="form-textarea" maxlength="1000" placeholder="Keterangan singkat mata pelajaran (opsional).">{{ old('deskripsi', $mataPelajaran?->deskripsi) }}</textarea>
            @error('deskripsi') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <label class="flex items-start gap-3 rounded-sm border border-line px-4 py-3">
            <input type="checkbox" name="status_aktif" value="1" class="mt-0.5 h-4 w-4 rounded border-line-strong text-moss focus:ring-moss" @checked(old('status_aktif', $mataPelajaran?->status_aktif ?? true))>
            <span>
                <span class="block text-sm font-medium text-ink">Mata pelajaran aktif</span>
                <span class="block text-xs text-ink-muted">Hanya mata pelajaran aktif yang dapat dipilih pada penugasan dan jadwal baru.</span>
            </span>
        </label>
    </div>
</div>

<div class="mt-6 flex flex-wrap items-center justify-end gap-3">
    <a href="{{ route('admin.mata-pelajaran.index') }}" class="btn-secondary">Batal</a>
    <button type="submit" class="btn-primary">
        <i data-lucide="save" class="h-4 w-4"></i>
        {{ $mataPelajaran ? 'Simpan Perubahan' : 'Simpan Mata Pelajaran' }}
    </button>
</div>
