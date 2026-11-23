@extends('layouts.app')

@section('judul', 'Isi Absensi')
@section('deskripsi')
    {{ $sesi->jadwal?->mataPelajaran?->nama_mata_pelajaran }} · {{ $sesi->jadwal?->kelas?->nama_kelas }} · {{ $sesi->tanggal_pelajaran?->format('d/m/Y') }}
@endsection

@section('aksi')
    @if ($bolehMengisi)
        <a href="{{ route('guru.scanner.show', $sesi) }}" class="btn-secondary btn-sm"><i data-lucide="scan-line" class="h-4 w-4"></i> Mode QR</a>
    @endif
    <a href="{{ route('guru.sesi.show', $sesi) }}" class="btn-ghost btn-sm">Kembali</a>
@endsection

@section('konten')
@php
    $statusAwal = [];
    $keteranganAwal = [];

    foreach ($siswa as $murid) {
        $baris = $catatan->get($murid->id_siswa);
        $statusAwal[$murid->id_siswa] = old(
            'absensi.'.$murid->id_siswa.'.status',
            $baris?->status_kehadiran ?? \App\Models\CatatanAbsensi::HADIR
        );
        $keteranganAwal[$murid->id_siswa] = old(
            'absensi.'.$murid->id_siswa.'.keterangan',
            $baris?->keterangan
        );
    }
@endphp

<div class="grid gap-6 xl:grid-cols-4">
    <div class="space-y-6 xl:col-span-3">
        <div class="card overflow-hidden">
            <div class="border-l-2 border-l-accent bg-paper px-6 py-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="kicker">Pengisian absensi</p>
                        <h2 class="mt-1.5 font-serif text-xl font-semibold text-ink">{{ $sesi->jadwal?->mataPelajaran?->nama_mata_pelajaran ?? 'Sesi' }}</h2>
                        <p class="mt-1 text-[12.5px] text-ink-muted">
                            {{ $sesi->jadwal?->kelas?->nama_kelas }} ·
                            {{ $sesi->tanggal_pelajaran?->locale('id')->translatedFormat('l, d F Y') }} ·
                            {{ $sesi->waktu_mulai?->format('H:i') }}–{{ $sesi->waktu_selesai?->format('H:i') }}
                        </p>
                    </div>
                    <x-status :status="$sesi->status_sesi" kelompok="SESI" />
                </div>
                <div class="mt-4 inline-flex items-center gap-2 border border-line bg-surface px-3 py-1.5 font-mono text-[10px] uppercase tracking-[0.08em] text-ink-soft">
                    <i data-lucide="timer" class="h-3.5 w-3.5 text-accent"></i>
                    @if ($jendelaTerbuka)
                        Jendela absensi terbuka · {{ $sesi->waktu_absensi_dibuka?->format('H:i') }}–{{ $sesi->waktu_absensi_ditutup?->format('H:i') }}
                    @else
                        Jendela absensi tertutup · {{ $sesi->waktu_absensi_dibuka?->format('H:i') }}–{{ $sesi->waktu_absensi_ditutup?->format('H:i') }}
                    @endif
                </div>
            </div>
            @if ($bolehMengisi)
                <div class="flex items-start gap-3 border-t border-moss-soft bg-moss-soft/70 px-6 py-3 text-sm text-moss">
                    <i data-lucide="info" class="mt-0.5 h-4 w-4 shrink-0"></i>
                    <p>Seluruh siswa wajib memiliki status. Setelah disimpan, sistem mengirim permintaan konfirmasi kepada petugas siswa. Perubahan masih dimungkinkan hingga jendela ditutup.</p>
                </div>
            @else
                <div class="flex items-start gap-3 border-t border-line bg-paper px-6 py-3 text-sm text-ink-soft">
                    <i data-lucide="lock" class="mt-0.5 h-4 w-4 shrink-0"></i>
                    <p>Form pengisian tidak tersedia. Absensi hanya dapat diisi selama jendela terbuka oleh guru pengampu atau guru piket yang berhak.</p>
                </div>
            @endif
        </div>

        @if ($siswa->isEmpty())
            <div class="card">
                <x-empty judul="Tidak ada siswa" pesan="Tidak ada siswa aktif pada kelas ini di tanggal sesi." ikon="users" />
            </div>
        @elseif ($bolehMengisi)
            <form method="POST" action="{{ route('guru.absensi.simpan', $sesi) }}"
                  x-data="{
                      statuses: {{ \Illuminate\Support\Js::from($statusAwal) }},
                      semuaHadir() {
                          Object.keys(this.statuses).forEach((kunci) => { this.statuses[kunci] = 'hadir'; });
                      },
                      jumlah(nilai) {
                          return Object.values(this.statuses).filter((status) => status === nilai).length;
                      },
                  }">
                @csrf

                <div class="card overflow-hidden">
                    <div class="card-header flex-wrap gap-3">
                        <div>
                            <p class="text-sm font-semibold text-ink">Daftar siswa</p>
                            <p class="mt-0.5 text-xs text-ink-muted">{{ $siswa->count() }} siswa · pilih status lalu lengkapi keterangan bila perlu.</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 border border-moss/25 bg-moss-soft px-2 py-1 font-mono text-[10px] uppercase tracking-[0.08em] text-moss">
                                H <span x-text="jumlah('hadir')">{{ collect($statusAwal)->filter(fn ($s) => $s === 'hadir')->count() }}</span>
                            </span>
                            <span class="inline-flex items-center gap-1.5 border border-ochre/25 bg-ochre-soft px-2 py-1 font-mono text-[10px] uppercase tracking-[0.08em] text-ochre">
                                I <span x-text="jumlah('izin')">{{ collect($statusAwal)->filter(fn ($s) => $s === 'izin')->count() }}</span>
                            </span>
                            <span class="inline-flex items-center gap-1.5 border border-slateblue/25 bg-slateblue-soft px-2 py-1 font-mono text-[10px] uppercase tracking-[0.08em] text-slateblue">
                                S <span x-text="jumlah('sakit')">{{ collect($statusAwal)->filter(fn ($s) => $s === 'sakit')->count() }}</span>
                            </span>
                            <span class="inline-flex items-center gap-1.5 border border-clay/25 bg-clay-soft px-2 py-1 font-mono text-[10px] uppercase tracking-[0.08em] text-clay">
                                A <span x-text="jumlah('alpha')">{{ collect($statusAwal)->filter(fn ($s) => $s === 'alpha')->count() }}</span>
                            </span>
                            <button type="button" class="btn-secondary btn-sm" @click="semuaHadir()">
                                <i data-lucide="check-check" class="h-3.5 w-3.5"></i> Tandai Semua Hadir
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-line">
                            <thead class="table-head">
                                <tr>
                                    <th class="px-5 py-3">Siswa</th>
                                    <th class="px-4 py-3">Status kehadiran</th>
                                    <th class="px-5 py-3">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                @foreach ($siswa as $murid)
                                    @php
                                        $id = $murid->id_siswa;
                                        $baris = $catatan->get($id);
                                    @endphp
                                    <tr class="align-top hover:bg-paper/60">
                                        <td class="px-5 py-3">
                                            <p class="text-sm font-medium text-ink">{{ $murid->nama_lengkap }}</p>
                                            <p class="text-xs text-ink-muted">NIS {{ $murid->nis }}</p>
                                            @if ($baris?->waktu_kehadiran)
                                                <p class="mt-0.5 text-[11px] text-moss">Tercatat {{ $baris->waktu_kehadiran->format('H:i:s') }}</p>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="inline-flex overflow-hidden rounded-sm border border-line bg-surface shadow-none">
                                                @foreach (['hadir' => ['H', 'Hadir', 'peer-checked:bg-moss'], 'izin' => ['I', 'Izin', 'peer-checked:bg-ochre'], 'sakit' => ['S', 'Sakit', 'peer-checked:bg-slateblue'], 'alpha' => ['A', 'Alpha', 'peer-checked:bg-clay']] as $nilai => [$huruf, $label, $aktif])
                                                    <label class="cursor-pointer border-r border-line last:border-r-0" title="{{ $label }}">
                                                        <input type="radio"
                                                               name="absensi[{{ $id }}][status]"
                                                               value="{{ $nilai }}"
                                                               x-model="statuses[{{ $id }}]"
                                                               class="peer sr-only"
                                                               @checked($statusAwal[$id] === $nilai)>
                                                        <span class="flex h-9 w-11 items-center justify-center text-xs font-semibold text-ink-muted transition peer-focus-visible:ring-2 peer-focus-visible:ring-moss peer-focus-visible:ring-inset peer-checked:text-paper {{ $aktif }}">{{ $huruf }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="px-5 py-3">
                                            <input type="text"
                                                   name="absensi[{{ $id }}][keterangan]"
                                                   value="{{ $keteranganAwal[$id] }}"
                                                   class="form-input"
                                                   maxlength="255"
                                                   placeholder="Opsional, misal: surat dokter">
                                            @error('absensi.'.$id.'.status')
                                                <p class="form-error">{{ $message }}</p>
                                            @enderror
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line bg-paper/70 px-5 py-4">
                        <p class="text-xs text-ink-muted">
                            <i data-lucide="shield-check" class="mr-1 inline h-3.5 w-3.5 text-moss"></i>
                            Setiap perubahan status dicatat pada audit absensi.
                        </p>
                        <button type="submit" class="btn-primary" onclick="return confirm('Simpan absensi sesi ini? Permintaan konfirmasi akan dikirim ke petugas siswa.')">
                            <i data-lucide="save" class="h-4 w-4"></i> Simpan Absensi
                        </button>
                    </div>
                </div>
            </form>
        @else
            <div class="card overflow-hidden">
                <div class="card-header">
                    <div>
                        <p class="text-sm font-semibold text-ink">Daftar siswa</p>
                        <p class="mt-0.5 text-xs text-ink-muted">{{ $catatan->count() }} dari {{ $siswa->count() }} siswa memiliki catatan.</p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-line">
                        <thead class="table-head">
                            <tr>
                                <th class="px-5 py-3">Siswa</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Metode</th>
                                <th class="px-4 py-3">Waktu</th>
                                <th class="px-5 py-3">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($siswa as $murid)
                                @php($baris = $catatan->get($murid->id_siswa))
                                <tr>
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
                                    <td class="px-5 py-3 text-xs text-ink-muted">{{ $baris?->keterangan ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    <div class="space-y-6">
        <div class="card">
            <div class="card-header">
                <p class="text-sm font-semibold text-ink">Panduan singkat</p>
            </div>
            <div class="card-body space-y-3 text-sm text-ink-soft">
                <div class="flex gap-3">
                    <span class="grid h-7 w-7 shrink-0 place-items-center rounded-sm bg-moss-soft text-xs font-bold text-moss">1</span>
                    <p>Tentukan status setiap siswa. Gunakan tombol <strong>Tandai Semua Hadir</strong> lalu sesuaikan pengecualian.</p>
                </div>
                <div class="flex gap-3">
                    <span class="grid h-7 w-7 shrink-0 place-items-center rounded-sm bg-moss-soft text-xs font-bold text-moss">2</span>
                    <p>Isi keterangan untuk izin, sakit, atau alpha agar dapat ditelusuri pada laporan.</p>
                </div>
                <div class="flex gap-3">
                    <span class="grid h-7 w-7 shrink-0 place-items-center rounded-sm bg-moss-soft text-xs font-bold text-moss">3</span>
                    <p>Simpan. Permintaan konfirmasi dikirim otomatis ke petugas siswa dengan batas 5 menit setelah jam pelajaran berakhir.</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <p class="text-sm font-semibold text-ink">Informasi sesi</p>
            </div>
            <dl class="divide-y divide-line text-sm">
                <div class="flex items-center justify-between px-5 py-3">
                    <dt class="text-ink-muted">Guru penanggung jawab</dt>
                    <dd class="font-medium text-ink">{{ $sesi->guruPenanggungJawab?->nama_lengkap ?? '—' }}</dd>
                </div>
                <div class="flex items-center justify-between px-5 py-3">
                    <dt class="text-ink-muted">Guru pelaksana</dt>
                    <dd class="font-medium text-ink">{{ $sesi->guruPelaksana?->nama_lengkap ?? 'Belum ada' }}</dd>
                </div>
                <div class="flex items-center justify-between px-5 py-3">
                    <dt class="text-ink-muted">Batas konfirmasi</dt>
                    <dd class="font-medium text-ink">{{ $sesi->waktu_batas_konfirmasi?->format('H:i') ?? '—' }}</dd>
                </div>
                <div class="flex items-center justify-between px-5 py-3">
                    <dt class="text-ink-muted">Status sesi</dt>
                    <dd><x-status :status="$sesi->status_sesi" kelompok="SESI" /></dd>
                </div>
            </dl>
        </div>
    </div>
</div>
@endsection
