{{--
    6-digit code entry; paste works; fires `otp-complete` with the code when all boxes are filled.
    <x-ui.otp-input wire:model="code" label="Verification code" x-on:otp-complete="$wire.verify()" />
--}}
@props(['length' => 6, 'label' => null, 'error' => null, 'name' => null, 'autofocus' => true])
@php
    $key = \App\Support\FieldName::key($attributes, $name);
    $error ??= $key !== null ? $errors->first($key) : null;
@endphp
<div x-data="otpInput({ length: {{ (int) $length }} })" {{ $attributes->only(['class', 'x-on:otp-complete'])->class(['min-w-0']) }}>
    @if ($label)
        <p class="mb-2 text-sm font-medium text-ink" id="{{ $key }}-label">{{ $label }}</p>
    @endif
    <input type="hidden" x-ref="value" @if ($name) name="{{ $name }}" @endif {{ $attributes->except(['class', 'x-on:otp-complete']) }}>
    <div class="flex gap-2 sm:gap-2.5" role="group" @if ($label) aria-labelledby="{{ $key }}-label" @endif>
        @for ($i = 0; $i < $length; $i++)
            <input type="text" inputmode="numeric" autocomplete="{{ $i === 0 ? 'one-time-code' : 'off' }}" maxlength="{{ $i === 0 ? $length : 1 }}"
                data-otp-box
                @if ($i === 0 && $autofocus) autofocus @endif
                aria-label="{{ __('Digit :n', ['n' => $i + 1]) }}"
                x-on:input="input({{ $i }}, $event)"
                x-on:keydown="keydown({{ $i }}, $event)"
                x-on:paste="paste($event)"
                x-on:focus="$event.target.select()"
                @class(['cos-control tabular h-12 w-11 px-0 text-center text-lg font-semibold sm:w-12', 'cos-control-invalid' => $error])>
            @if ($length === 6 && $i === 2)
                <span class="self-center text-ink-subtle" aria-hidden="true">–</span>
            @endif
        @endfor
    </div>
    @if ($error)
        <p class="mt-2 text-xs text-danger" role="alert">{{ $error }}</p>
    @endif
</div>
