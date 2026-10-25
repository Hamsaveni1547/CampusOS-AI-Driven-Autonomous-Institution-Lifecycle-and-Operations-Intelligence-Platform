{{--
    <x-ui.radio-group label="Staff type" wire:model.live="staffType" :options="['teaching' => 'Teaching', 'non_teaching' => 'Non-teaching']" />
    cards: show each option as a selectable card (options may then be [value => ['label' => .., 'description' => ..]]).
--}}
@props(['label' => null, 'hint' => null, 'error' => null, 'name' => null, 'options' => [], 'cards' => false, 'inline' => false])
@php
    $key = \App\Support\FieldName::key($attributes, $name);
    $id = \App\Support\FieldName::id($attributes, $key);
    $error ??= $key !== null ? $errors->first($key) : null;
    $groupName = $name ?? $id;
@endphp
<fieldset {{ $attributes->only('class') }} @if ($error) aria-invalid="true" @endif>
    @if ($label)
        <legend class="mb-2 text-sm font-medium text-ink">{{ $label }}</legend>
    @endif
    <div @class(['grid gap-3 sm:grid-cols-2' => $cards, 'flex flex-wrap gap-x-6 gap-y-2' => ! $cards && $inline, 'space-y-2' => ! $cards && ! $inline])>
        @foreach ($options as $value => $option)
            @php
                $optionLabel = is_array($option) ? $option['label'] : $option;
                $optionDescription = is_array($option) ? ($option['description'] ?? null) : null;
                $optionId = $id.'-'.\Illuminate\Support\Str::slug((string) $value);
            @endphp
            <label for="{{ $optionId }}" @class([
                'flex cursor-pointer items-start gap-3',
                'rounded-lg border border-line bg-surface p-3.5 transition hover:border-line-strong has-[:checked]:border-primary has-[:checked]:bg-primary-soft has-[:checked]:ring-1 has-[:checked]:ring-primary' => $cards,
            ])>
                <input type="radio" id="{{ $optionId }}" name="{{ $groupName }}" value="{{ $value }}"
                    {{ $attributes->except('class')->class(['mt-0.5 size-4 shrink-0 border-line-strong bg-surface text-primary focus:ring-2 focus:ring-primary/30 focus:ring-offset-0 dark:bg-surface-muted']) }}>
                <span class="min-w-0 text-sm">
                    <span class="font-medium text-ink">{{ $optionLabel }}</span>
                    @if ($optionDescription)<span class="mt-0.5 block text-ink-muted">{{ $optionDescription }}</span>@endif
                </span>
            </label>
        @endforeach
    </div>
    @if ($error)
        <p class="mt-1.5 text-xs text-danger">{{ $error }}</p>
    @elseif ($hint)
        <p class="mt-1.5 text-xs text-ink-muted">{{ $hint }}</p>
    @endif
</fieldset>
