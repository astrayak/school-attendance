@extends('layouts.app')

@section('judul', 'Detail Siswa')
@section('deskripsi', $siswa->nama_lengkap . ' · NIS ' . $siswa->nis)

@section('aksi')
    <a href="{{ route('admin.siswa.edit', $siswa) }}" class="btn-secondary btn-sm">
        <i data-lucide="pencil" class="h-4 w-4"></i> Ubah
    </a>
    <a href="{{ route('admin.siswa.index') }}" class="btn-ghost btn-sm">Kembali</a>
@endsection

@section('konten')
<div class="grid gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
        <div class="card overflow-hidden">
            <div class="border-l-2 border-l-accent bg-paper px-6 py-6">
                <div class="flex flex-wrap items-center gap-5">
                    <x-avatar :pengguna="$siswa->pengguna" :nama="$siswa->nama_lengkap" ukuran="lg" />
                    <div class="min-w-0 flex-1">
                        <p class="kicker">Profil siswa</p>
                        <h2 class="mt-1 font-serif text-xl font-semibold tracking-tight text-ink">{{ $siswa->nama_lengkap }}</h2>
                        <p class="mt-1 text-[12.5px] text-ink-muted">
                            NIS {{ $siswa->nis }}{{ $siswa->nisn ? ' · NISN '.$siswa->nisn : '' }}
                            · {{ $kelasSaatIni?->nama_kelas ?? 'Belum ditempatkan' }}
                        </p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <x-badge :teks="ucfirst($siswa->status_siswa)" :warna="$siswa->status_siswa === 'aktif' ? 'bg-moss-soft text-moss ring-moss/25' : 'bg-paper-deep text-ink-muted ring-line-strong'" />
                            <x-badge :teks="$siswa->pengguna?->status_aktif ? 'Akun aktif' : 'Akun nonaktif'"
                                :warna="$siswa->pengguna?->status_aktif ? 'bg-moss-soft text-moss ring-moss/25' : 'bg-clay-soft text-clay ring-clay/25'" />
                        </div>
                    </div>
                    <div class="border-l border-line pl-5 text-right">
                        <p class="kicker">Kehadiran periode</p>
                        <p class="mt-1 font-serif text-[28px] font-semibold leading-none tabular-nums text-ink">{{ $persentase }}%</p>
                        <p class="mt-1 font-mono text-[9.5px] uppercase tracking-[0.06em] text-ink-muted">{{ $dari->format('d/m/Y') }} – {{ $sampai->format('d/m/Y') }}</p>
                    </div>
                </div>
            </div>

            <div class="grid gap-5 px-6 py-5 sm:grid-cols-3">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">Email</p>
                    <p class="mt-1 text-sm font-medium text-ink">{{ $siswa->pengguna?->email }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">Telepon siswa</p>
                    <p class="mt-1 text-sm font-medium text-ink">{{ $siswa->nomor_telepon ?: '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">Terakhir login</p>
                    <p class="mt-1 text-sm font-medium text-ink">
                        {{ $siswa->pengguna?->terakhir_login?->locale('id')->translatedFormat('d M Y H:i') ?? 'Belum pernah' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">Jenis kelamin</p>
                    <p class="mt-1 text-sm font-medium text-ink">{{ $siswa->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">Tempat, tanggal lahir</p>
                    <p class="mt-1 text-sm font-medium text-ink">
                        {{ $siswa->tempat_lahir ?: '—' }}{{ $siswa->tanggal_lahir ? ', '.$siswa->tanggal_lahir->format('d/m/Y') : '' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">Agama</p>
                    <p class="mt-1 text-sm font-medium text-ink">{{ $siswa->agama ?: '—' }}</p>
                </div>
                <div class="sm:col-span-2">
                    <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">Alamat</p>
                    <p class="mt-1 text-sm text-ink-soft">{{ $siswa->alamat ?: '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">Orang tua/wali</p>
                    <p class="mt-1 text-sm font-medium text-ink">{{ $siswa->nama_orang_tua ?: '—' }}</p>
                    <p class="text-xs text-ink-muted">{{ $siswa->nomor_telepon_orang_tua ?: '' }}</p>
                </div>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card label="Hadir" :nilai="$rekapMapel->sum('hadir')" ikon="check-circle-2" warna="emerald" />
            <x-stat-card label="Izin" :nilai="$rekapMapel->sum('izin')" ikon="file-text" warna="amber" />
            <x-stat-card label="Sakit" :nilai="$rekapMapel->sum('sakit')" ikon="thermometer" warna="sky" />
            <x-stat-card label="Alpha" :nilai="$rekapMapel->sum('alpha')" ikon="x-circle" warna="rose" />
        </div>

        <div class="card overflow-hidden">
            <div class="card-header">
                <div>
                    <p class="text-sm font-semibold text-ink">Rekap per mata pelajaran</p>
                    <p class="mt-0.5 text-xs text-ink-muted">Periode {{ $dari->format('d/m/Y') }} – {{ $sampai->format('d/m/Y') }}.</p>
                </div>
            </div>
            @if ($rekapMapel->isEmpty())
                <x-empty judul="Belum ada rekap mapel" pesan="Sesi absensi yang sudah berakhir akan muncul di sini." ikon="book-open" />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-line text-sm">
                        <thead class="table-head">
                            <tr>
                                <th class="px-5 py-3">Mata pelajaran</th>
                                <th class="px-3 py-3 text-center">Pertemuan</th>
                                <th class="px-3 py-3 text-center">H</th>
                                <th class="px-3 py-3 text-center">I</th>
                                <th class="px-3 py-3 text-center">S</th>
                                <th class="px-3 py-3 text-center">A</th>
                                <th class="px-3 py-3 text-center">Belum</th>
                                <th class="px-5 py-3 text-right">Hadir</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($rekapMapel as $baris)
                                <tr>
                                    <td class="px-5 py-3 font-medium text-ink">{{ $baris['nama_mata_pelajaran'] }}</td>
                                    <td class="px-3 py-3 text-center text-ink-soft">{{ $baris['total_pertemuan'] }}</td>
                                    <td class="px-3 py-3 text-center text-moss">{{ $baris['hadir'] }}</td>
                                    <td class="px-3 py-3 text-center text-ochre">{{ $baris['izin'] }}</td>
                                    <td class="px-3 py-3 text-center text-slateblue">{{ $baris['sakit'] }}</td>
                                    <td class="px-3 py-3 text-center text-clay">{{ $baris['alpha'] }}</td>
                                    <td class="px-3 py-3 text-center text-ink-muted">{{ $baris['belum_diisi'] }}</td>
                                    <td class="px-5 py-3 text-right font-semibold text-ink">{{ $baris['persentase_hadir'] }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="card overflow-hidden">
            <div class="card-header">
                <div>
                    <p class="text-sm font-semibold text-ink">Absensi terbaru</p>
                    <p class="mt-0.5 text-xs text-ink-muted">10 catatan terakhir siswa.</p>
                </div>
            </div>
            @if ($catatanTerbaru->isEmpty())
                <x-empty judul="Belum ada catatan absensi" ikon="list-checks" />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-line text-sm">
                        <thead class="table-head">
                            <tr>
                                <th class="px-5 py-3">Tanggal</th>
                                <th class="px-4 py-3">Mata pelajaran</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Metode</th>
                                <th class="px-5 py-3">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($catatanTerbaru as $catatan)
                                <tr>
                                    <td class="px-5 py-3 text-ink-soft">
                                        {{ $catatan->sesiAbsensi?->tanggal_pelajaran?->format('d/m/Y') }}
                                        <span class="block text-xs text-ink-muted">{{ $catatan->sesiAbsensi?->waktu_mulai?->format('H:i') }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-ink">{{ $catatan->sesiAbsensi?->jadwal?->mataPelajaran?->nama_mata_pelajaran ?? '—' }}</p>
                                        <p class="text-xs text-ink-muted">{{ $catatan->sesiAbsensi?->jadwal?->kelas?->nama_kelas }}</p>
                                    </td>
                                    <td class="px-4 py-3"><x-status :status="$catatan->status_kehadiran" kelompok="KEHADIRAN" /></td>
                                    <td class="px-4 py-3 text-xs text-ink-muted">{{ \App\Support\Status::label('METODE', $catatan->metode_absensi) }}</td>
                                    <td class="px-5 py-3 text-xs text-ink-muted">{{ $catatan->keterangan ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="space-y-6">
        <div class="card">
            <div class="card-header">
                <div>
                    <p class="text-sm font-semibold text-ink">QR Code siswa</p>
                    <p class="mt-0.5 text-xs text-ink-muted">Digunakan saat pemindaian absensi.</p>
                </div>
                <x-badge :teks="$siswa->kodeQrAktif ? 'Aktif' : 'Tidak ada'"
                    :warna="$siswa->kodeQrAktif ? 'bg-moss-soft text-moss ring-moss/20' : 'bg-clay-soft text-clay ring-clay/20'" />
            </div>
            <div class="card-body">
                @if ($qrSvg)
                    <div class="mx-auto w-full max-w-[240px] rounded-sm border border-line bg-surface p-3">
                        <div class="[&>svg]:h-auto [&>svg]:w-full">{!! $qrSvg !!}</div>
                    </div>
                    <p class="mt-3 text-center text-xs text-ink-muted">
                        Dibuat {{ $siswa->kodeQrAktif->tanggal_dibuat?->format('d/m/Y H:i') }}
                    </p>
                @else
                    <div class="rounded-sm border border-dashed border-line-strong px-4 py-8 text-center">
                        <i data-lucide="qr-code" class="mx-auto h-8 w-8 text-ink-muted"></i>
                        <p class="mt-2 text-sm text-ink-muted">Siswa belum memiliki QR Code aktif.</p>
                    </div>
                @endif

                <div class="mt-4 space-y-2">
                    <form method="POST" action="{{ route('admin.siswa.qr.regenerasi', $siswa) }}">
                        @csrf
                        <button type="submit" class="btn-secondary w-full" onclick="return confirm('Buat QR Code baru? QR Code lama akan langsung dicabut.')">
                            <i data-lucide="refresh-cw" class="h-4 w-4"></i>
                            Regenerasi QR
                        </button>
                    </form>
                    @if ($siswa->kodeQrAktif)
                        <form method="POST" action="{{ route('admin.siswa.qr.cabut', $siswa) }}">
                            @csrf
                            <button type="submit" class="btn-ghost w-full text-clay hover:bg-clay-soft" onclick="return confirm('Cabut QR Code aktif siswa ini?')">
                                <i data-lucide="ban" class="h-4 w-4"></i>
                                Cabut QR Aktif
                            </button>
                        </form>
                    @endif
                </div>
                <p class="mt-3 text-[11px] leading-relaxed text-ink-muted">
                    QR hanya menyimpan token acak yang di-hash di server. Jangan mengunggah gambar QR ke media publik.
                </p>
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="card-header">
                <p class="text-sm font-semibold text-ink">Riwayat kelas</p>
            </div>
            @if ($siswa->riwayatKelas->isEmpty())
                <x-empty judul="Belum ada riwayat kelas" ikon="school" />
            @else
                <ul class="divide-y divide-line">
                    @foreach ($siswa->riwayatKelas->sortByDesc('tanggal_mulai') as $riwayat)
                        <li class="flex items-start gap-3 px-5 py-3">
                            <span class="mt-0.5 grid h-8 w-8 shrink-0 place-items-center rounded-sm {{ $riwayat->status_aktif ? 'bg-moss-soft text-moss' : 'bg-paper-deep text-ink-muted' }}">
                                <i data-lucide="school" class="h-4 w-4"></i>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-ink">{{ $riwayat->kelas?->nama_kelas }}</p>
                                <p class="text-xs text-ink-muted">
                                    {{ $riwayat->tanggal_mulai?->format('d/m/Y') }} –
                                    {{ $riwayat->tanggal_selesai?->format('d/m/Y') ?? 'sekarang' }}
                                    · {{ $riwayat->kelas?->tahunAjaran?->nama_tahun_ajaran }}
                                </p>
                            </div>
                            @if ($riwayat->status_aktif)
                                <x-badge teks="Aktif" warna="bg-moss-soft text-moss ring-moss/20" />
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="card overflow-hidden">
            <div class="card-header">
                <p class="text-sm font-semibold text-ink">Riwayat konfirmasi</p>
            </div>
            @php($konfirmasi = $siswa->permintaanKonfirmasi->sortByDesc('dibuat_pada')->take(5))
            @if ($konfirmasi->isEmpty())
                <x-empty judul="Belum pernah menjadi petugas" ikon="badge-check" />
            @else
                <ul class="divide-y divide-line">
                    @foreach ($konfirmasi as $item)
                        <li class="px-5 py-3">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-medium text-ink">{{ $item->sesiAbsensi?->jadwal?->mataPelajaran?->nama_mata_pelajaran ?? '—' }}</p>
                                <x-status :status="$item->status_permintaan" kelompok="KONFIRMASI" />
                            </div>
                            <p class="mt-0.5 text-xs text-ink-muted">
                                {{ $item->sesiAbsensi?->tanggal_pelajaran?->format('d/m/Y') }} ·
                                {{ \App\Support\Status::label('JAWABAN', $item->jawaban) }}
                            </p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>
@endsection
