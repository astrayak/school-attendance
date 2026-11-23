@extends('layouts.app')

@section('judul', 'Detail Sesi Absensi')
@section('deskripsi')
    {{ $sesi->jadwal?->mataPelajaran?->nama_mata_pelajaran }} · {{ $sesi->jadwal?->kelas?->nama_kelas }} · {{ $sesi->tanggal_pelajaran?->format('d/m/Y') }}
@endsection

@section('aksi')
    @if ($bolehMengisi ?? false)
        <a href="{{ route('guru.absensi.show', $sesi) }}" class="btn-secondary btn-sm">Isi Absensi</a>
    @endif
    <a href="{{ route('admin.sesi.index', ['tanggal' => $sesi->tanggal_pelajaran?->toDateString()]) }}" class="btn-ghost btn-sm">Kembali</a>
@endsection

@section('konten')
<div class="grid gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
        <div class="card overflow-hidden">
            <div class="border-l-2 border-l-accent bg-paper px-6 py-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="kicker">Sesi pembelajaran</p>
                        <h2 class="mt-1.5 font-serif text-xl font-semibold tracking-tight text-ink">{{ $sesi->jadwal?->mataPelajaran?->nama_mata_pelajaran ?? 'Sesi tanpa jadwal' }}</h2>
                        <p class="mt-1 text-[12.5px] text-ink-muted">
                            {{ $sesi->jadwal?->kelas?->nama_kelas }} · {{ $sesi->tanggal_pelajaran?->locale('id')->translatedFormat('l, d F Y') }}
                            · {{ $sesi->waktu_mulai?->format('H:i') }}–{{ $sesi->waktu_selesai?->format('H:i') }}
                        </p>
                    </div>
                    <x-status :status="$sesi->status_sesi" kelompok="SESI" />
                </div>
                <div class="mt-5 grid gap-3 text-sm sm:grid-cols-2">
                    <div class="border border-line bg-surface px-3 py-2">
                        <p class="kicker">Guru penanggung jawab</p>
                        <p class="mt-1 text-[12.5px] font-bold text-ink">{{ $sesi->guruPenanggungJawab?->nama_lengkap ?? '—' }}</p>
                    </div>
                    <div class="border border-line bg-surface px-3 py-2">
                        <p class="kicker">Guru pelaksana</p>
                        <p class="mt-1 text-[12.5px] font-bold text-ink">{{ $sesi->guruPelaksana?->nama_lengkap ?? 'Belum ada' }}</p>
                    </div>
                </div>
            </div>
            <div class="grid gap-4 px-6 py-5 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">Jendela absensi</p>
                    <p class="mt-1 text-sm font-medium text-ink">{{ $sesi->waktu_absensi_dibuka?->format('H:i') }} – {{ $sesi->waktu_absensi_ditutup?->format('H:i') }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">Batas konfirmasi</p>
                    <p class="mt-1 text-sm font-medium text-ink">{{ $sesi->waktu_batas_konfirmasi?->format('H:i') ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">Metode</p>
                    <p class="mt-1 text-sm font-medium text-ink">{{ \App\Support\Status::label('METODE', $sesi->metode_absensi) }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">Disimpan</p>
                    <p class="mt-1 text-sm font-medium text-ink">{{ $sesi->waktu_disimpan?->format('d/m/Y H:i') ?? 'Belum' }}</p>
                </div>
                @if ($sesi->diambil_alih_pada)
                    <div class="sm:col-span-2 lg:col-span-4">
                        <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">Pengambilalihan piket</p>
                        <p class="mt-1 text-sm text-ink-soft">
                            {{ $sesi->diambil_alih_pada?->format('d/m/Y H:i') }} oleh {{ $sesi->guruPelaksana?->nama_lengkap }}.
                            Alasan: {{ $sesi->alasan_pengambilalihan ?: '—' }}
                        </p>
                    </div>
                @endif
                @if ($sesi->status_sesi === 'dibatalkan')
                    <div class="sm:col-span-2 lg:col-span-4 rounded-sm bg-clay-soft px-4 py-3 text-sm text-clay ring-1 ring-inset ring-clay-soft">
                        Sesi dibatalkan. Alasan: {{ $sesi->alasan_pembatalan ?: '—' }}
                    </div>
                @endif
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @php($hitung = $catatan->groupBy('status_kehadiran'))
            <x-stat-card label="Hadir" :nilai="$hitung->get('hadir', collect())->count()" ikon="check-circle-2" warna="emerald" />
            <x-stat-card label="Izin" :nilai="$hitung->get('izin', collect())->count()" ikon="file-text" warna="amber" />
            <x-stat-card label="Sakit" :nilai="$hitung->get('sakit', collect())->count()" ikon="thermometer" warna="sky" />
            <x-stat-card label="Alpha" :nilai="$hitung->get('alpha', collect())->count()" ikon="x-circle" warna="rose" />
        </div>

        <div class="card overflow-hidden">
            <div class="card-header">
                <div>
                    <p class="text-sm font-semibold text-ink">Kehadiran siswa</p>
                    <p class="mt-0.5 text-xs text-ink-muted">{{ $catatan->count() }} dari {{ $siswa->count() }} siswa memiliki catatan.</p>
                </div>
            </div>
            @if ($siswa->isEmpty())
                <x-empty judul="Tidak ada siswa" pesan="Tidak ada siswa aktif pada kelas ini di tanggal sesi." ikon="users" />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-line">
                        <thead class="table-head">
                            <tr>
                                <th class="px-5 py-3">Siswa</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Metode</th>
                                <th class="px-4 py-3">Waktu</th>
                                <th class="px-4 py-3">Pencatat</th>
                                <th class="px-5 py-3">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($siswa as $murid)
                                @php($baris = $catatan->get($murid->id_siswa))
                                <tr class="align-top">
                                    <td class="px-5 py-3">
                                        <p class="text-sm font-medium text-ink">{{ $murid->nama_lengkap }}</p>
                                        <p class="text-xs text-ink-muted">NIS {{ $murid->nis }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($baris)
                                            <x-status :status="$baris->status_kehadiran" kelompok="KEHADIRAN" />
                                        @else
                                            <x-badge teks="Belum diisi" warna="bg-paper-deep text-ink-soft ring-ink-muted/20" />
                                        @endif
                                    </td>
                                    <td class="table-cell">{{ $baris ? \App\Support\Status::label('METODE', $baris->metode_absensi) : '—' }}</td>
                                    <td class="table-cell">{{ $baris?->waktu_kehadiran?->format('H:i:s') ?? '—' }}</td>
                                    <td class="table-cell">{{ $baris?->pencatat?->nama_pengguna ?? '—' }}</td>
                                    <td class="px-5 py-3">
                                        <p class="text-xs text-ink-soft">{{ $baris?->keterangan ?: '—' }}</p>
                                        @if ($baris && ($audit->get($baris->id_catatan_absensi)?->isNotEmpty()))
                                            <details class="mt-1">
                                                <summary class="cursor-pointer text-xs font-medium text-moss">Riwayat perubahan</summary>
                                                <ul class="mt-1 space-y-1 border-l border-line pl-3">
                                                    @foreach ($audit->get($baris->id_catatan_absensi) as $perubahan)
                                                        <li class="text-[11px] text-ink-muted">
                                                            {{ $perubahan->waktu_perubahan?->format('d/m/Y H:i') }} ·
                                                            {{ \App\Support\Status::label('KEHADIRAN', $perubahan->status_sebelumnya) }} →
                                                            {{ \App\Support\Status::label('KEHADIRAN', $perubahan->status_sesudahnya) }}
                                                            · {{ $perubahan->pengguna?->nama_pengguna }}
                                                            @if ($perubahan->alasan_perubahan)
                                                                <span class="block">{{ $perubahan->alasan_perubahan }}</span>
                                                            @endif
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </details>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        @if ($bolehKoreksi && $sesi->status_sesi !== 'dibatalkan' && $siswa->isNotEmpty())
            <div class="card" x-data="{ buka: false }">
                <div class="card-header">
                    <div>
                        <p class="text-sm font-semibold text-ink">Koreksi absensi</p>
                        <p class="mt-0.5 text-xs text-ink-muted">Setiap perubahan dicatat pada audit absensi.</p>
                    </div>
                    <button type="button" class="btn-secondary btn-sm" @click="buka = !buka" x-text="buka ? 'Tutup' : 'Buka Form Koreksi'">Buka Form Koreksi</button>
                </div>
                <div x-cloak x-show="buka" x-transition class="card-body">
                    <form method="POST" action="{{ route('admin.sesi.koreksi', $sesi) }}" class="space-y-4">
                        @csrf
                        <div class="overflow-x-auto rounded-sm border border-line">
                            <table class="min-w-full divide-y divide-line">
                                <thead class="table-head">
                                    <tr>
                                        <th class="px-4 py-3">Siswa</th>
                                        <th class="px-4 py-3">Status</th>
                                        <th class="px-4 py-3">Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-line">
                                    @foreach ($siswa as $murid)
                                        @php($baris = $catatan->get($murid->id_siswa))
                                        <tr>
                                            <td class="px-4 py-2 text-sm text-ink-soft">{{ $murid->nama_lengkap }}<span class="block text-xs text-ink-muted">{{ $murid->nis }}</span></td>
                                            <td class="px-4 py-2">
                                                <select name="absensi[{{ $murid->id_siswa }}][status]" class="form-select" required>
                                                    @foreach (\App\Support\Status::KEHADIRAN as $nilai => $label)
                                                        <option value="{{ $nilai }}" @selected(old('absensi.'.$murid->id_siswa.'.status', $baris?->status_kehadiran ?? 'hadir') === $nilai)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="px-4 py-2">
                                                <input type="text" name="absensi[{{ $murid->id_siswa }}][keterangan]" value="{{ old('absensi.'.$murid->id_siswa.'.keterangan', $baris?->keterangan) }}" class="form-input" maxlength="255" placeholder="Opsional">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div>
                            <label for="alasan" class="form-label">Alasan koreksi <span class="text-clay">*</span></label>
                            <input type="text" id="alasan" name="alasan" class="form-input" value="{{ old('alasan') }}" required minlength="5" maxlength="255" placeholder="Contoh: memperbaiki status siswa yang terlambat hadir">
                            @error('alasan') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="btn-primary" onclick="return confirm('Simpan koreksi absensi? Perubahan akan tercatat di audit.')">
                                <i data-lucide="save" class="h-4 w-4"></i> Simpan Koreksi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>

    <div class="space-y-6">
        @if ($bolehBatal)
            <div class="card border-clay-soft">
                <div class="card-header">
                    <div>
                        <p class="text-sm font-semibold text-clay">Batalkan sesi</p>
                        <p class="mt-0.5 text-xs text-clay">Sesi batal tidak dihitung dalam rekap.</p>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.sesi.batalkan', $sesi) }}" class="space-y-3">
                        @csrf
                        <input type="text" name="alasan" class="form-input" required minlength="5" maxlength="255" placeholder="Alasan pembatalan">
                        <button type="submit" class="btn-danger w-full" onclick="return confirm('Batalkan sesi ini? Tindakan tidak dapat dibatalkan.')">
                            <i data-lucide="ban" class="h-4 w-4"></i> Batalkan Sesi
                        </button>
                    </form>
                </div>
            </div>
        @endif

        <div class="card overflow-hidden">
            <div class="card-header">
                <div>
                    <p class="text-sm font-semibold text-ink">Permintaan konfirmasi</p>
                    <p class="mt-0.5 text-xs text-ink-muted">{{ $sesi->permintaanKonfirmasi->count() }} permintaan</p>
                </div>
            </div>
            @if ($sesi->permintaanKonfirmasi->isEmpty())
                <x-empty judul="Belum ada permintaan" pesan="Permintaan dibuat setelah absensi disimpan." ikon="badge-check" />
            @else
                <ul class="divide-y divide-line">
                    @foreach ($sesi->permintaanKonfirmasi->sortBy('urutan_penerima') as $permintaan)
                        <li class="px-5 py-3">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-medium text-ink">{{ $permintaan->siswaPenerima?->nama_lengkap ?? '—' }}</p>
                                <x-status :status="$permintaan->status_permintaan" kelompok="KONFIRMASI" />
                            </div>
                            <p class="mt-0.5 text-xs text-ink-muted">
                                Prioritas {{ $permintaan->urutan_penerima }} ·
                                dikirim {{ $permintaan->waktu_dikirim?->format('H:i:s') }} ·
                                batas {{ $permintaan->waktu_kedaluwarsa?->format('H:i:s') }}
                            </p>
                            @if ($permintaan->jawaban)
                                <p class="mt-1 text-xs font-medium text-ink-soft">{{ \App\Support\Status::label('JAWABAN', $permintaan->jawaban) }}</p>
                            @endif
                            @if ($permintaan->keterangan)
                                <p class="mt-0.5 text-xs text-ink-muted">{{ $permintaan->keterangan }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="card overflow-hidden">
            <div class="card-header">
                <p class="text-sm font-semibold text-ink">Aktivitas sesi</p>
            </div>
            @if ($riwayatSesi->isEmpty())
                <x-empty judul="Belum ada aktivitas" ikon="history" />
            @else
                <ul class="divide-y divide-line">
                    @foreach ($riwayatSesi as $log)
                        <li class="px-5 py-3">
                            <p class="text-sm text-ink-soft">{{ $log->deskripsi_aktivitas }}</p>
                            <p class="mt-0.5 text-xs text-ink-muted">
                                {{ $log->waktu_aktivitas?->format('d/m/Y H:i') }} · {{ $log->pengguna?->nama_pengguna ?? 'Sistem' }}
                            </p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>
@endsection
