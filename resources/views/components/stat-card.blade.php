@props(['label', 'nilai', 'ikon' => 'activity', 'warna' => 'ink', 'keterangan' => null])
@php
    $palet = [
        'ink' => 'text-ink-soft',
        'moss' => 'text-moss',
        'ochre' => 'text-ochre',
        'slateblue' => 'text-slateblue',
        'clay' => 'text-clay',
        'accent' => 'text-accent',
        'emerald' => 'text-moss',
        'amber' => 'text-ochre',
        'sky' => 'text-slateblue',
        'rose' => 'text-clay',
        'violet' => 'text-slateblue',
        'slate' => 'text-ink-soft',
    ];
@endphp
<div {{ $attributes->merge(['class' => 'card h-full p-4']) }}>
    <div class="flex items-start justify-between gap-3">
        <p class="kicker">{{ $label }}</p>
        <i data-lucide="{{ $ikon }}" class="h-4 w-4 shrink-0 {{ $palet[$warna] ?? $palet['ink'] }}"></i>
    </div>
    <p class="mt-3 font-serif text-[34px] font-semibold leading-none tabular-nums tracking-tight text-ink">{{ $nilai }}</p>
    @if ($keterangan)
        <div class="mt-3 border-t border-line pt-2">
            <p class="text-[11px] leading-snug text-ink-muted">{{ $keterangan }}</p>
        </div>
    @endif
</div>
