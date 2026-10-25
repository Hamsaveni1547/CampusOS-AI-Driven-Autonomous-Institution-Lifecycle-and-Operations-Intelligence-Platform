{{--
    Drag & drop upload with progress (Livewire WithFileUploads).
    <x-ui.file-upload label="Staff CSV" wire:model="file" accept=".csv" max-kb="10240" hint="Columns: name, email, …" />
    Slot shows the current file / preview when set.
--}}
@props(['label' => null, 'hint' => null, 'error' => null, 'name' => null, 'accept' => null, 'maxKb' => null, 'icon' => 'upload'])
@php
    $key = \App\Support\FieldName::key($attributes, $name);
    $id = \App\Support\FieldName::id($attributes, $key);
    $error ??= $key !== null ? $errors->first($key) : null;
@endphp
<x-ui.field :label="$label" :for="$id" :error="$error" class="{{ $attributes->get('class') }}">
    <div
        x-data="fileUpload({ maxKb: {{ $maxKb ? (int) $maxKb : 'null' }}, accept: @js($accept) })"
        x-on:livewire-upload-start="start()"
        x-on:livewire-upload-finish="finish()"
        x-on:livewire-upload-error="fail()"
        x-on:livewire-upload-progress="advance($event)"
        x-on:dragover.prevent="dragging = true"
        x-on:dragleave.prevent="dragging = false"
        x-on:drop.prevent="drop($event)"
        :class="dragging ? 'border-primary bg-primary-soft' : 'border-line-strong bg-surface-muted/40 hover:border-primary/60'"
        @class(['rounded-lg border-2 border-dashed p-5 text-center transition', 'border-danger!' => $error])
    >
        <input type="file" id="{{ $id }}" class="sr-only" x-on:change="changed()" @if ($accept) accept="{{ $accept }}" @endif @if ($name) name="{{ $name }}" @endif {{ $attributes->except('class') }}>

        <div x-show="! uploading" class="flex flex-col items-center gap-2">
            <span class="flex size-10 items-center justify-center rounded-full bg-surface text-ink-muted shadow-xs">
                <x-ui.icon :name="$icon" class="size-5" />
            </span>
            <p class="text-sm text-ink">
                <button type="button" x-on:click="browse()" class="font-medium text-primary hover:underline">{{ __('Choose a file') }}</button>
                {{ __('or drag it here') }}
            </p>
            <p class="text-xs text-ink-muted" x-show="! fileName">{{ $hint }}</p>
            <p class="text-xs font-medium text-ink" x-show="fileName" x-text="fileName" x-cloak></p>
        </div>

        <div x-show="uploading" x-cloak class="mx-auto max-w-xs">
            <p class="mb-2 text-sm text-ink-muted">{{ __('Uploading…') }} <span x-text="progress + '%'" class="tabular"></span></p>
            <div class="h-2 overflow-hidden rounded-full bg-surface"><div class="h-full rounded-full bg-primary transition-[width]" :style="`width: ${progress}%`"></div></div>
        </div>

        <p x-show="error" x-text="error" x-cloak class="mt-2 text-xs text-danger"></p>
    </div>
    @if (trim((string) $slot) !== '')
        <div class="mt-3">{{ $slot }}</div>
    @endif
</x-ui.field>
