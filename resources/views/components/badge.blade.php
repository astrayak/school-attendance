@props(['teks' => null, 'warna' => 'bg-paper-deep text-ink-muted ring-line-strong'])
<span {{ $attributes->merge(['class' => 'badge '.$warna]) }}>{{ $teks ?? $slot }}</span>
