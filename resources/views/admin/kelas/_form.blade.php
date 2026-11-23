@php($kelas = $kelas ?? null)

<div class="card">
    <div class="card-header">
        <div>
            <p class="text-sm font-semibold text-ink">Informasi Kelas</p>
            <p class="mt-0.5 text-xs text-ink-muted">Kelas terikat pada satu tahun ajaran.</p>
        </div>
    </div>
    <div class="card-body grid gap-4 sm:grid-cols-2">
        <div>
            <label for="nama_kelas" class="form-label">Nama kelas <span class="text-clay">*</span></label>
            <input type="text" id="nama_kelas" name="nama_kelas" value="{{ old('nama_kelas', $kelas?->nama_kelas) }}" class="form-input" placeholder="Contoh: X IPA 1" required maxlength="50">
            @error('nama_kelas') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="tingkat" class="form-label">Tingkat <span class="text-clay">*</span></label>
            <input type="text" id="tingkat" name="tingkat" value="{{ old('tingkat', $kelas?->tingkat) }}" class="form-input" placeholder="Contoh: X, XI, XII" required maxlength="10">
            @error('tingkat') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="jurusan" class="form-label">Jurusan</label>
            <input type="text" id="jurusan" name="jurusan" value="{{ old('jurusan', $kelas?->jurusan) }}" class="form-input" placeholder="Contoh: IPA, IPS, TKJ" maxlength="50">
            @error('jurusan') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="id_tahun_ajaran" class="form-label">Tahun ajaran <span class="text-clay">*</span></label>
            <select id="id_tahun_ajaran" name="id_tahun_ajaran" class="form-select" required>
                <option value="">Pilih tahun ajaran</option>
                @foreach ($tahunAjaran as $tahun)
                    <option value="{{ $tahun->id_tahun_ajaran }}" @selected((string) old('id_tahun_ajaran', $kelas?->id_tahun_ajaran) === (string) $tahun->id_tahun_ajaran)>
                        {{ $tahun->nama_tahun_ajaran }} ({{ $tahun->tanggal_mulai?->format('Y') }}–{{ $tahun->tanggal_selesai?->format('Y') }})
                    </option>
                @endforeach
            </select>
            @error('id_tahun_ajaran') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="wali_kelas" class="form-label">Wali kelas</label>
            <select id="wali_kelas" name="wali_kelas" class="form-select">
                <option value="">Belum ditentukan</option>
                @foreach ($guru as $item)
                    <option value="{{ $item->id_guru }}" @selected((string) old('wali_kelas', $kelas?->wali_kelas) === (string) $item->id_guru)>{{ $item->nama_lengkap }}</option>
                @endforeach
            </select>
            @error('wali_kelas') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="status_kelas" class="form-label">Status kelas <span class="text-clay">*</span></label>
            <select id="status_kelas" name="status_kelas" class="form-select" required>
                <option value="aktif" @selected(old('status_kelas', $kelas?->status_kelas ?? 'aktif') === 'aktif')>Aktif</option>
                <option value="nonaktif" @selected(old('status_kelas', $kelas?->status_kelas) === 'nonaktif')>Nonaktif</option>
            </select>
            @error('status_kelas') <p class="form-error">{{ $message }}</p> @enderror
        </div>
    </div>
</div>

<div class="mt-6 flex flex-wrap items-center justify-end gap-3">
    <a href="{{ route('admin.kelas.index') }}" class="btn-secondary">Batal</a>
    <button type="submit" class="btn-primary">
        <i data-lucide="save" class="h-4 w-4"></i>
        {{ $kelas ? 'Simpan Perubahan' : 'Simpan Kelas' }}
    </button>
</div>
