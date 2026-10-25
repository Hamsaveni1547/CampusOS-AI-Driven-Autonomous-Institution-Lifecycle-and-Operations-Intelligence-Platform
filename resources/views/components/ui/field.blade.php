{{--
    Label + control + hint + error. Usually used through the controls' own label/hint props,
    but can wrap anything:
    <x-ui.field label="Department" for="dept" :error="$errors->first('department_id')" hint="Optional">...</x-ui.field>
--}}
@props(['label' => null, 'for' => null, 'hint' => null, 'error' => null, 'required' => false, 'optional' => false, 'inline' => false])
<div {{ $attributes->class(['min-w-0']) }}>
    @if ($label)
        <div class="mb-1.5 flex items-baseline justify-between gap-2">
            <label @if ($for) for="{{ $for }}" @endif class="text-sm font-medium text-ink">
                {{ $label }}
                @if ($required)<span class="text-danger" aria-hidden="true">*</span>@endif
            </label>
            @if ($optional)
                <span class="text-xs text-ink-subtle">{{ __('Optional') }}</span>
            @endif
            {{ $corner ?? '' }}
        </div>
    @endif

    {{ $slot }}

    @if ($error)
        <p id="{{ $for }}-error" class="mt-1.5 flex items-start gap-1 text-xs text-danger">
            <x-ui.icon name="circle-alert" class="mt-px size-3.5" />
            <span>{{ $error }}</span>
        </p>
    @elseif ($hint)
        <p id="{{ $for }}-hint" class="mt-1.5 text-xs text-ink-muted">{{ $hint }}</p>
    @endif
</div>
