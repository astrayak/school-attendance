@props(['judul' => 'Belum ada data', 'pesan' => null, 'ikon' => 'inbox'])
<div class="flex flex-col items-center justify-center px-6 py-14 text-center">
    <span class="grid h-11 w-11 place-items-center border border-line text-ink-muted">
        <i data-lucide="{{ $ikon }}" class="h-5 w-5"></i>
    </span>
    <p class="mt-4 font-serif text-[17px] font-semibold text-ink">{{ $judul }}</p>
    @if ($pesan)
        <p class="mt-1.5 max-w-md text-[12.5px] leading-relaxed text-ink-muted">{{ $pesan }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>
