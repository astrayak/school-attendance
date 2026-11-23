@extends('layouts.app')

@section('judul', 'Konfirmasi Pembelajaran')
@section('deskripsi', 'Konfirmasi apakah sesi pembelajaran benar-benar berlangsung.')

@section('aksi')
    <a href="{{ route('siswa.dashboard') }}" class="btn-ghost btn-sm">Dashboard</a>
@endsection

@section('konten')
@if ($jumlahMenunggu > 0)
    <div class="mb-6 flex items-start gap-3 rounded-sm border border-ochre-soft bg-ochre-soft px-4 py-3 text-sm text-ochre">
        <i data-lucide="bell-ring" class="mt-0.5 h-5 w-5 shrink-0"></i>
        <p><strong>{{ $jumlahMenunggu }} permintaan menunggu jawaban.</strong> Jawab sebelum batas waktu. Bila tidak dijawab, permintaan dialihkan ke petugas berikutnya.</p>
    </div>
@endif

<div class="space-y-4">
    @forelse ($daftar as $permintaan)
        @php($sesi = $permintaan->sesiAbsensi)
        @php($menunggu = $permintaan->status_permintaan === \App\Models\PermintaanKonfirmasi::MENUNGGU)
        <div class="card overflow-hidden {{ $menunggu ? 'ring-1 ring-ochre-soft' : '' }}">
            <div class="card-body">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="flex min-w-0 items-start gap-3">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-sm {{ $menunggu ? 'bg-ochre-soft text-ochre' : 'bg-paper-deep text-ink-muted' }}">
                            <i data-lucide="{{ $menunggu ? 'badge-alert' : 'badge-check' }}" class="h-5 w-5"></i>
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-ink">{{ $sesi?->jadwal?->mataPelajaran?->nama_mata_pelajaran ?? 'Sesi pembelajaran' }}</p>
                            <p class="mt-0.5 text-xs text-ink-muted">
                                {{ $sesi?->jadwal?->kelas?->nama_kelas }} ·
                                {{ $sesi?->tanggal_pelajaran?->locale('id')->translatedFormat('l, d F Y') }} ·
                                {{ $sesi?->waktu_mulai?->format('H:i') }}–{{ $sesi?->waktu_selesai?->format('H:i') }}
                            </p>
                            <p class="mt-0.5 text-xs text-ink-muted">
                                Guru: {{ $sesi?->guruPelaksana?->nama_lengkap ?? $sesi?->guruPenanggungJawab?->nama_lengkap ?? '—' }}
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <x-status :status="$permintaan->status_permintaan" kelompok="KONFIRMASI" />
                        @if ($menunggu)
                            <div class="mt-1.5" x-data="hitungMundur('{{ $permintaan->waktu_kedaluwarsa?->toIso8601String() }}')">
                                <p class="text-[11px] uppercase tracking-wide text-ink-muted">Sisa waktu</p>
                                <p class="font-mono text-lg font-semibold" :class="lewat ? 'text-clay' : 'text-ochre'" x-text="sisa">—</p>
                            </div>
                        @endif
                    </div>
                </div>

                @if ($permintaan->jawaban)
                    <div class="mt-4 flex items-center gap-2 rounded-sm bg-paper px-3 py-2 text-sm text-ink-soft">
                        <i data-lucide="message-square" class="h-4 w-4 text-ink-muted"></i>
                        <span>Jawaban Anda: <strong>{{ \App\Support\Status::label('JAWABAN', $permintaan->jawaban) }}</strong></span>
                    </div>
                @endif

                @if ($permintaan->keterangan)
                    <p class="mt-2 text-xs text-ink-muted">Keterangan: {{ $permintaan->keterangan }}</p>
                @endif

                @if ($menunggu)
                    <div class="mt-4 rounded-sm border border-line bg-paper/70 p-4">
                        <p class="text-sm font-medium text-ink">Apakah pembelajaran ini berlangsung?</p>
                        <p class="mt-0.5 text-xs text-ink-muted">Jawaban Anda membantu memastikan absensi sesuai dengan kenyataan.</p>
                        <form method="POST" action="{{ route('siswa.konfirmasi.jawab', $permintaan) }}" class="mt-3 space-y-3">
                            @csrf
                            <input type="text" name="keterangan" class="form-input" maxlength="255" placeholder="Keterangan tambahan (opsional)">
                            <div class="flex flex-col gap-2 sm:flex-row">
                                <button type="submit" name="jawaban" value="{{ \App\Models\PermintaanKonfirmasi::BERLANGSUNG }}" class="btn-primary flex-1">
                                    <i data-lucide="check" class="h-4 w-4"></i> Ya, pembelajaran berlangsung
                                </button>
                                <button type="submit" name="jawaban" value="{{ \App\Models\PermintaanKonfirmasi::TIDAK_BERLANGSUNG }}" class="btn-danger flex-1" onclick="return confirm('Laporkan bahwa pembelajaran tidak berlangsung? Admin akan memeriksa sesi ini.')">
                                    <i data-lucide="x" class="h-4 w-4"></i> Tidak berlangsung
                                </button>
                            </div>
                        </form>
                    </div>
                @endif

                <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-ink-muted">
                    <span>Dikirim {{ $permintaan->waktu_dikirim?->format('d/m/Y H:i') }}</span>
                    <span>Batas {{ $permintaan->waktu_kedaluwarsa?->format('d/m/Y H:i') }}</span>
                    @if ($permintaan->waktu_direspons)
                        <span>Direspons {{ $permintaan->waktu_direspons->format('d/m/Y H:i') }}</span>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="card">
            <x-empty judul="Tidak ada permintaan" pesan="Saat ini tidak ada permintaan konfirmasi untuk Anda. Permintaan muncul setelah guru menyimpan absensi." ikon="badge-check" />
        </div>
    @endforelse
</div>
@endsection
