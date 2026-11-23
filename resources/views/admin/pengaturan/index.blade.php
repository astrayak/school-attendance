@extends('layouts.app')

@section('judul', 'Pengaturan Sistem')
@section('deskripsi', 'Jendela absensi, batas konfirmasi, dan zona waktu.')

@section('konten')
<div class="grid gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
        <form method="POST" action="{{ route('admin.pengaturan.store') }}">
            @csrf
            <div class="card">
                <div class="card-header">
                    <div>
                        <p class="text-sm font-semibold text-ink">Jendela Waktu</p>
                        <p class="mt-0.5 text-xs text-ink-muted">Perubahan berlaku untuk sesi baru pada tanggal hari ini.</p>
                    </div>
                </div>
                <div class="card-body grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="menit_buka_sebelum_selesai" class="form-label">Buka sebelum selesai (menit)</label>
                        <input type="number" id="menit_buka_sebelum_selesai" name="menit_buka_sebelum_selesai" value="{{ old('menit_buka_sebelum_selesai', $nilai['menit_buka_sebelum_selesai']) }}" class="form-input" min="0" max="120" required>
                        <p class="form-help">Absensi dibuka {{ $nilai['menit_buka_sebelum_selesai'] }} menit sebelum jam pelajaran berakhir.</p>
                        @error('menit_buka_sebelum_selesai') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="menit_tutup_setelah_selesai" class="form-label">Tutup setelah selesai (menit)</label>
                        <input type="number" id="menit_tutup_setelah_selesai" name="menit_tutup_setelah_selesai" value="{{ old('menit_tutup_setelah_selesai', $nilai['menit_tutup_setelah_selesai']) }}" class="form-input" min="0" max="120" required>
                        <p class="form-help">Nilai 0 berarti tepat saat pelajaran berakhir.</p>
                        @error('menit_tutup_setelah_selesai') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="menit_batas_konfirmasi" class="form-label">Batas konfirmasi (menit)</label>
                        <input type="number" id="menit_batas_konfirmasi" name="menit_batas_konfirmasi" value="{{ old('menit_batas_konfirmasi', $nilai['menit_batas_konfirmasi']) }}" class="form-input" min="1" max="120" required>
                        <p class="form-help">Batas total setelah pelajaran berakhir untuk seluruh alihan petugas.</p>
                        @error('menit_batas_konfirmasi') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="detik_per_alihan_konfirmasi" class="form-label">Jeda alihan konfirmasi (detik)</label>
                        <input type="number" id="detik_per_alihan_konfirmasi" name="detik_per_alihan_konfirmasi" value="{{ old('detik_per_alihan_konfirmasi', $nilai['detik_per_alihan_konfirmasi']) }}" class="form-input" min="10" max="3600" required>
                        <p class="form-help">Waktu respons sebelum permintaan dialihkan ke petugas berikutnya.</p>
                        @error('detik_per_alihan_konfirmasi') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="zona_waktu" class="form-label">Zona waktu aplikasi</label>
                        <select id="zona_waktu" name="zona_waktu" class="form-select" required>
                            @foreach ($zonaWaktu as $zona)
                                <option value="{{ $zona }}" @selected(old('zona_waktu', $nilai['zona_waktu']) === $zona)>{{ $zona }}</option>
                            @endforeach
                        </select>
                        <p class="form-help">Jadwal dan batas waktu disimpan dengan zona waktu ini.</p>
                        @error('zona_waktu') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="card mt-6">
                <div class="card-header">
                    <p class="text-sm font-semibold text-ink">Otomatisasi</p>
                </div>
                <div class="card-body space-y-3">
                    <label class="flex items-start gap-3 rounded-sm border border-line px-4 py-3">
                        <input type="checkbox" name="kunci_absensi_otomatis" value="1" class="mt-0.5 h-4 w-4 rounded border-line-strong text-moss focus:ring-moss" @checked(old('kunci_absensi_otomatis', $nilai['kunci_absensi_otomatis']))>
                        <span>
                            <span class="block text-sm font-medium text-ink">Kunci absensi otomatis</span>
                            <span class="block text-xs text-ink-muted">Sesi ditutup saat jendela berakhir sehingga guru tidak dapat mengisi lagi.</span>
                        </span>
                    </label>
                    <label class="flex items-start gap-3 rounded-sm border border-line px-4 py-3">
                        <input type="checkbox" name="pengalihan_konfirmasi_otomatis" value="1" class="mt-0.5 h-4 w-4 rounded border-line-strong text-moss focus:ring-moss" @checked(old('pengalihan_konfirmasi_otomatis', $nilai['pengalihan_konfirmasi_otomatis']))>
                        <span>
                            <span class="block text-sm font-medium text-ink">Alihkan konfirmasi otomatis</span>
                            <span class="block text-xs text-ink-muted">Permintaan berpindah ke petugas prioritas berikutnya bila tidak direspons.</span>
                        </span>
                    </label>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit" class="btn-primary"><i data-lucide="save" class="h-4 w-4"></i> Simpan Pengaturan</button>
            </div>
        </form>
    </div>

    <div class="card h-fit overflow-hidden">
        <div class="card-header">
            <div>
                <p class="text-sm font-semibold text-ink">Riwayat perubahan</p>
                <p class="mt-0.5 text-xs text-ink-muted">20 pengaturan terakhir.</p>
            </div>
        </div>
        @if ($riwayat->isEmpty())
            <x-empty judul="Belum ada riwayat" pesan="Pengaturan bawaan masih digunakan." ikon="settings" />
        @else
            <ul class="divide-y divide-line">
                @foreach ($riwayat as $item)
                    <li class="px-5 py-3">
                        <p class="text-xs font-medium text-ink-soft">{{ \App\Services\LayananPengaturan::LABEL[$item->kunci] ?? $item->kunci }}</p>
                        <p class="mt-0.5 text-sm text-ink">{{ $item->nilai }}</p>
                        <p class="text-[11px] text-ink-muted">
                            {{ $item->tanggal_berlaku?->format('d/m/Y') }} · {{ $item->pembuat?->nama_pengguna ?? 'Sistem' }}
                        </p>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
@endsection
