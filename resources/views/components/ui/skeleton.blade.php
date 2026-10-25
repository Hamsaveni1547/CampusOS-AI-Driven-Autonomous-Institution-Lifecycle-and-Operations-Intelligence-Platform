{{--
    Loading placeholder. <x-ui.skeleton class="h-4 w-40" />  or  <x-ui.skeleton lines="3" />  or  <x-ui.skeleton type="table" rows="5" />
--}}
@props(['lines' => null, 'type' => null, 'rows' => 5])
@if ($type === 'table')
    <div {{ $attributes->class(['divide-y divide-line']) }} aria-hidden="true">
        @for ($i = 0; $i < $rows; $i++)
            <div class="flex items-center gap-4 px-4 py-3.5">
                <div class="size-8 animate-pulse rounded-full bg-surface-muted"></div>
                <div class="h-3.5 w-1/4 animate-pulse rounded bg-surface-muted"></div>
                <div class="h-3.5 w-1/5 animate-pulse rounded bg-surface-muted"></div>
                <div class="ml-auto h-3.5 w-16 animate-pulse rounded bg-surface-muted"></div>
            </div>
        @endfor
    </div>
@elseif ($lines)
    <div {{ $attributes->class(['space-y-2.5']) }} aria-hidden="true">
        @for ($i = 0; $i < (int) $lines; $i++)
            <div class="h-3.5 animate-pulse rounded bg-surface-muted" style="width: {{ $i === (int) $lines - 1 ? 60 : 100 - $i * 6 }}%"></div>
        @endfor
    </div>
@else
    <div {{ $attributes->class(['animate-pulse rounded bg-surface-muted']) }} aria-hidden="true"></div>
@endif
