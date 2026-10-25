{{--
    <x-ui.avatar name="Anita Rao" />  initials on a colour picked from the name
    <x-ui.avatar name="Anita Rao" :src="$url" size="lg" />
    size: xs | sm | md | lg | xl
--}}
@props(['name' => '', 'src' => null, 'size' => 'md'])
@php
    $sizes = ['xs' => 'size-6 text-[10px]', 'sm' => 'size-8 text-xs', 'md' => 'size-9 text-sm', 'lg' => 'size-12 text-base', 'xl' => 'size-16 text-lg'];
    $palette = ['bg-indigo-100 text-indigo-700', 'bg-emerald-100 text-emerald-700', 'bg-amber-100 text-amber-800', 'bg-sky-100 text-sky-700', 'bg-rose-100 text-rose-700', 'bg-violet-100 text-violet-700', 'bg-teal-100 text-teal-700'];
    $parts = array_values(array_filter(preg_split('/\s+/', trim((string) $name)) ?: []));
    $initials = mb_strtoupper(mb_substr($parts[0] ?? '?', 0, 1).(count($parts) > 1 ? mb_substr($parts[count($parts) - 1], 0, 1) : ''));
    $colour = $palette[abs(crc32((string) $name)) % count($palette)];
@endphp
<span {{ $attributes->class(['relative inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full font-semibold', $sizes[$size] ?? $sizes['md'], $src ? '' : $colour.' dark:opacity-90']) }}
    title="{{ $name }}">
    @if ($src)
        <img src="{{ $src }}" alt="{{ $name }}" class="size-full object-cover" loading="lazy">
    @else
        <span aria-hidden="true">{{ $initials }}</span>
        <span class="sr-only">{{ $name }}</span>
    @endif
</span>
