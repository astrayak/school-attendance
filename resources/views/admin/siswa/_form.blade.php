@php
    $sedangEdit = isset($siswa);
    $kelasTerpilih = old('id_kelas', $sedangEdit ? ($kelasSaatIni?->id_kelas ?? null) : null);
@endphp

<div class="grid gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
        <div class="card">
            <div class="card-header">
                <div>
                    <p class="text-sm font-semibold text-ink">Akun pengguna</p>
                    <p class="mt-0.5 text-xs text-ink-muted">Digunakan siswa untuk masuk ke sistem.</p>
                </div>
            </div>
            <div class="card-body grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="nama_pengguna" class="form-label">Nama pengguna <span class="text-clay">*</span></label>
                    <input id="nama_pengguna" name="nama_pengguna" type="text" required maxlength="120"
                        value="{{ old('nama_pengguna', $siswa->pengguna->nama_pengguna ?? '') }}" class="form-input">
                </div>
                <div>
                    <label for="email" class="form-label">Email <span class="text-clay">*</span></label>
                    <input id="email" name="email" type="email" required maxlength="150"
                        value="{{ old('email', $siswa->pengguna->email ?? '') }}" class="form-input">
                </div>
                <div>
                    <label for="kata_sandi" class="form-label">Kata sandi {{ $sedangEdit ? '(opsional)' : '' }}</label>
                    <input id="kata_sandi" name="kata_sandi" type="password" minlength="6" autocomplete="new-password"
                        class="form-input" placeholder="{{ $sedangEdit ? 'Kosongkan bila tidak diubah' : 'Minimal 6 karakter' }}">
                </div>
                <div class="flex items-center pt-6">
                    <label class="flex items-center gap-2.5 text-sm text-ink-soft">
                        <input type="checkbox" name="status_aktif" value="1" @checked(old('status_aktif', $siswa->pengguna->status_aktif ?? true))
                            class="h-4 w-4 rounded border-line-strong text-moss focus:ring-moss">
                        Akun aktif dan dapat login
                    </label>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <p class="text-sm font-semibold text-ink">Data siswa</p>
                    <p class="mt-0.5 text-xs text-ink-muted">Informasi identitas dan status kesiswaan.</p>
                </div>
            </div>
            <div class="card-body grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="nis" class="form-label">NIS <span class="text-clay">*</span></label>
                    <input id="nis" name="nis" type="text" required maxlength="30"
                        value="{{ old('nis', $siswa->nis ?? '') }}" class="form-input">
                </div>
                <div>
                    <label for="nisn" class="form-label">NISN</label>
                    <input id="nisn" name="nisn" type="text" maxlength="30"
                        value="{{ old('nisn', $siswa->nisn ?? '') }}" class="form-input">
                </div>
                <div class="sm:col-span-2">
                    <label for="nama_lengkap" class="form-label">Nama lengkap <span class="text-clay">*</span></label>
                    <input id="nama_lengkap" name="nama_lengkap" type="text" required maxlength="150"
                        value="{{ old('nama_lengkap', $siswa->nama_lengkap ?? '') }}" class="form-input">
                </div>
                <div>
                    <label for="jenis_kelamin" class="form-label">Jenis kelamin <span class="text-clay">*</span></label>
                    <select id="jenis_kelamin" name="jenis_kelamin" required class="form-select">
                        <option value="L" @selected(old('jenis_kelamin', $siswa->jenis_kelamin ?? '') === 'L')>Laki-laki</option>
                        <option value="P" @selected(old('jenis_kelamin', $siswa->jenis_kelamin ?? '') === 'P')>Perempuan</option>
                    </select>
                </div>
                <div>
                    <label for="status_siswa" class="form-label">Status siswa <span class="text-clay">*</span></label>
                    <select id="status_siswa" name="status_siswa" required class="form-select">
                        @foreach (['aktif' => 'Aktif', 'lulus' => 'Lulus', 'pindah' => 'Pindah', 'nonaktif' => 'Nonaktif'] as $nilai => $label)
                            <option value="{{ $nilai }}" @selected(old('status_siswa', $siswa->status_siswa ?? 'aktif') === $nilai)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="tempat_lahir" class="form-label">Tempat lahir</label>
                    <input id="tempat_lahir" name="tempat_lahir" type="text" maxlength="100"
                        value="{{ old('tempat_lahir', $siswa->tempat_lahir ?? '') }}" class="form-input">
                </div>
                <div>
                    <label for="tanggal_lahir" class="form-label">Tanggal lahir</label>
                    <input id="tanggal_lahir" name="tanggal_lahir" type="date"
                        value="{{ old('tanggal_lahir', isset($siswa) && $siswa->tanggal_lahir ? $siswa->tanggal_lahir->format('Y-m-d') : '') }}" class="form-input">
                </div>
                <div>
                    <label for="agama" class="form-label">Agama</label>
                    <input id="agama" name="agama" type="text" maxlength="30"
                        value="{{ old('agama', $siswa->agama ?? '') }}" class="form-input">
                </div>
                <div>
                    <label for="nomor_telepon" class="form-label">Nomor telepon siswa</label>
                    <input id="nomor_telepon" name="nomor_telepon" type="text" maxlength="25"
                        value="{{ old('nomor_telepon', $siswa->nomor_telepon ?? '') }}" class="form-input">
                </div>
                <div class="sm:col-span-2">
                    <label for="alamat" class="form-label">Alamat</label>
                    <textarea id="alamat" name="alamat" rows="2" maxlength="1000" class="form-textarea">{{ old('alamat', $siswa->alamat ?? '') }}</textarea>
                </div>
                <div>
                    <label for="nama_orang_tua" class="form-label">Nama orang tua/wali</label>
                    <input id="nama_orang_tua" name="nama_orang_tua" type="text" maxlength="150"
                        value="{{ old('nama_orang_tua', $siswa->nama_orang_tua ?? '') }}" class="form-input">
                </div>
                <div>
                    <label for="nomor_telepon_orang_tua" class="form-label">Nomor telepon orang tua/wali</label>
                    <input id="nomor_telepon_orang_tua" name="nomor_telepon_orang_tua" type="text" maxlength="25"
                        value="{{ old('nomor_telepon_orang_tua', $siswa->nomor_telepon_orang_tua ?? '') }}" class="form-input">
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <p class="text-sm font-semibold text-ink">{{ $sedangEdit ? 'Penempatan kelas' : 'Kelas awal' }}</p>
                    <p class="mt-0.5 text-xs text-ink-muted">
                        {{ $sedangEdit ? 'Pilih kelas baru hanya jika siswa pindah kelas. Riwayat lama tetap tersimpan.' : 'Opsional — siswa dapat ditempatkan ke kelas sekarang atau menyusul.' }}
                    </p>
                </div>
            </div>
            <div class="card-body grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="id_kelas" class="form-label">Kelas</label>
                    <select id="id_kelas" name="id_kelas" class="form-select">
                        <option value="">— Tidak ditempatkan —</option>
                        @foreach ($kelas as $item)
                            <option value="{{ $item->id_kelas }}" @selected((string) $kelasTerpilih === (string) $item->id_kelas)>
                                {{ $item->nama_kelas }} — {{ $item->tahunAjaran?->nama_tahun_ajaran }}
                            </option>
                        @endforeach
                    </select>
                    <p class="form-help">Daftar hanya menampilkan kelas berstatus aktif.</p>
                </div>
                <div>
                    <label for="tanggal_mulai_kelas" class="form-label">{{ $sedangEdit ? 'Tanggal pindah kelas' : 'Tanggal mulai kelas' }}</label>
                    <input id="tanggal_mulai_kelas" name="tanggal_mulai_kelas" type="date"
                        value="{{ old('tanggal_mulai_kelas', now()->format('Y-m-d')) }}" class="form-input">
                    <p class="form-help">{{ $sedangEdit ? 'Berlaku jika kelas diubah. Tanggal ini menjadi awal riwayat kelas baru.' : 'Kosongkan untuk menggunakan tanggal hari ini.' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-4">
        <div class="card p-5">
            <p class="text-sm font-semibold text-ink">Panduan singkat</p>
            <ul class="mt-3 space-y-2.5 text-xs leading-relaxed text-ink-soft">
                <li class="flex gap-2"><i data-lucide="check" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-moss"></i> NIS dan NISN harus unik di seluruh sekolah.</li>
                <li class="flex gap-2"><i data-lucide="check" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-moss"></i> QR Code dibuat otomatis saat siswa ditambahkan.</li>
                <li class="flex gap-2"><i data-lucide="check" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-moss"></i> Perpindahan kelas tidak menghapus riwayat absensi lama.</li>
                <li class="flex gap-2"><i data-lucide="check" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-moss"></i> Status akun nonaktif akan mencegah siswa login.</li>
            </ul>
        </div>

        <div class="card p-5">
            <p class="text-sm font-semibold text-ink">Aksi</p>
            <div class="mt-4 space-y-2">
                <button type="submit" class="btn-primary w-full">
                    <i data-lucide="save" class="h-4 w-4"></i>
                    {{ $sedangEdit ? 'Simpan Perubahan' : 'Simpan Siswa' }}
                </button>
                <a href="{{ $sedangEdit ? route('admin.siswa.show', $siswa) : route('admin.siswa.index') }}" class="btn-secondary w-full">Batal</a>
            </div>
        </div>
    </div>
</div>
