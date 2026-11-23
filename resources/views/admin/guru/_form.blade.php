@php($guru = $guru ?? null)

<div class="grid gap-6 xl:grid-cols-2">
    <div class="card">
        <div class="card-header">
            <div>
                <p class="text-sm font-semibold text-ink">Akun Pengguna</p>
                <p class="mt-0.5 text-xs text-ink-muted">Kredensial untuk masuk ke sistem.</p>
            </div>
        </div>
        <div class="card-body space-y-4">
            <div>
                <label for="nama_pengguna" class="form-label">Nama pengguna <span class="text-clay">*</span></label>
                <input type="text" id="nama_pengguna" name="nama_pengguna" value="{{ old('nama_pengguna', $guru?->pengguna?->nama_pengguna) }}" class="form-input" required maxlength="120" autocomplete="name">
                @error('nama_pengguna') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="email" class="form-label">Email <span class="text-clay">*</span></label>
                <input type="email" id="email" name="email" value="{{ old('email', $guru?->pengguna?->email) }}" class="form-input" required maxlength="150" autocomplete="email">
                @error('email') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="kata_sandi" class="form-label">Kata sandi {{ $guru ? '' : '*' }}</label>
                <input type="password" id="kata_sandi" name="kata_sandi" class="form-input" {{ $guru ? '' : 'required' }} minlength="6" autocomplete="new-password">
                <p class="form-help">{{ $guru ? 'Biarkan kosong bila tidak ingin mengganti kata sandi.' : 'Minimal 6 karakter.' }}</p>
                @error('kata_sandi') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <label class="flex items-start gap-3 rounded-sm border border-line px-4 py-3">
                <input type="checkbox" name="status_aktif" value="1" class="mt-0.5 h-4 w-4 rounded border-line-strong text-moss focus:ring-moss" @checked(old('status_aktif', $guru?->pengguna?->status_aktif ?? true))>
                <span>
                    <span class="block text-sm font-medium text-ink">Akun aktif</span>
                    <span class="block text-xs text-ink-muted">Pengguna nonaktif tidak dapat masuk ke sistem.</span>
                </span>
            </label>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <p class="text-sm font-semibold text-ink">Identitas Guru</p>
                <p class="mt-0.5 text-xs text-ink-muted">Data kepegawaian dan kontak.</p>
            </div>
        </div>
        <div class="card-body space-y-4">
            <div>
                <label for="nama_lengkap" class="form-label">Nama lengkap <span class="text-clay">*</span></label>
                <input type="text" id="nama_lengkap" name="nama_lengkap" value="{{ old('nama_lengkap', $guru?->nama_lengkap) }}" class="form-input" required maxlength="150">
                @error('nama_lengkap') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="nip" class="form-label">NIP</label>
                    <input type="text" id="nip" name="nip" value="{{ old('nip', $guru?->nip) }}" class="form-input" maxlength="30">
                    @error('nip') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="nuptk" class="form-label">NUPTK</label>
                    <input type="text" id="nuptk" name="nuptk" value="{{ old('nuptk', $guru?->nuptk) }}" class="form-input" maxlength="30">
                    @error('nuptk') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="jenis_kelamin" class="form-label">Jenis kelamin <span class="text-clay">*</span></label>
                    <select id="jenis_kelamin" name="jenis_kelamin" class="form-select" required>
                        <option value="L" @selected(old('jenis_kelamin', $guru?->jenis_kelamin) === 'L')>Laki-laki</option>
                        <option value="P" @selected(old('jenis_kelamin', $guru?->jenis_kelamin) === 'P')>Perempuan</option>
                    </select>
                    @error('jenis_kelamin') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="status_guru" class="form-label">Status guru <span class="text-clay">*</span></label>
                    <select id="status_guru" name="status_guru" class="form-select" required>
                        <option value="aktif" @selected(old('status_guru', $guru?->status_guru ?? 'aktif') === 'aktif')>Aktif</option>
                        <option value="nonaktif" @selected(old('status_guru', $guru?->status_guru) === 'nonaktif')>Nonaktif</option>
                    </select>
                    @error('status_guru') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="tempat_lahir" class="form-label">Tempat lahir</label>
                    <input type="text" id="tempat_lahir" name="tempat_lahir" value="{{ old('tempat_lahir', $guru?->tempat_lahir) }}" class="form-input" maxlength="100">
                    @error('tempat_lahir') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="tanggal_lahir" class="form-label">Tanggal lahir</label>
                    <input type="date" id="tanggal_lahir" name="tanggal_lahir" value="{{ old('tanggal_lahir', $guru?->tanggal_lahir?->format('Y-m-d')) }}" class="form-input">
                    @error('tanggal_lahir') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div>
                <label for="nomor_telepon" class="form-label">Nomor telepon</label>
                <input type="text" id="nomor_telepon" name="nomor_telepon" value="{{ old('nomor_telepon', $guru?->nomor_telepon) }}" class="form-input" maxlength="25">
                @error('nomor_telepon') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="alamat" class="form-label">Alamat</label>
                <textarea id="alamat" name="alamat" rows="3" class="form-textarea" maxlength="1000">{{ old('alamat', $guru?->alamat) }}</textarea>
                @error('alamat') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>
</div>

<div class="mt-6 flex flex-wrap items-center justify-end gap-3">
    <a href="{{ $guru ? route('admin.guru.show', $guru) : route('admin.guru.index') }}" class="btn-secondary">Batal</a>
    <button type="submit" class="btn-primary">
        <i data-lucide="save" class="h-4 w-4"></i>
        {{ $guru ? 'Simpan Perubahan' : 'Simpan Guru' }}
    </button>
</div>
