{{--
    Confirmation for destructive or important actions.
    <x-ui.confirm-dialog name="deactivate-user" title="Deactivate this user?" confirm-label="Deactivate"
        tone="danger" action="deactivate" confirm-text="Anita Rao">
        They will be signed out and cannot sign in until re-activated.
    </x-ui.confirm-dialog>
    action: Livewire method called on confirm. confirm-text: user must type it to enable the button.
    Slot "fields" adds inputs (e.g. a reason) above the buttons.
--}}
@props(['name', 'title', 'confirmLabel' => null, 'cancelLabel' => null, 'tone' => 'danger', 'action' => null, 'confirmText' => null, 'icon' => null])
@php
    $iconName = $icon ?? ($tone === 'danger' ? 'triangle-alert' : 'circle-help');
    $iconTone = $tone === 'danger' ? 'bg-danger-soft text-danger' : 'bg-primary-soft text-primary-ink';
@endphp
<x-ui.modal :name="$name" size="sm">
    <div x-data="{ typed: '' }" x-on:dialog-closed.window="if ($event.detail === @js($name)) typed = ''" class="flex gap-4">
        <span class="flex size-10 shrink-0 items-center justify-center rounded-full {{ $iconTone }}">
            <x-ui.icon :name="$iconName" class="size-5" />
        </span>
        <div class="min-w-0 flex-1">
            <h2 class="text-base font-semibold text-ink">{{ $title }}</h2>
            <div class="mt-1.5 text-sm text-ink-muted">{{ $slot }}</div>

            {{ $fields ?? '' }}

            @if ($confirmText)
                <div class="mt-4">
                    <label class="text-sm text-ink-muted">{!! __('Type :text to confirm', ['text' => '<span class="font-semibold text-ink">'.e($confirmText).'</span>']) !!}</label>
                    <input type="text" x-model="typed" class="cos-control mt-1.5" autocomplete="off" aria-label="{{ __('Confirmation text') }}">
                </div>
            @endif

            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', @js($name))">{{ $cancelLabel ?? __('Cancel') }}</x-ui.button>
                @php
                    $confirmAttributes = $attributes->only(['x-on:click'])->merge(array_filter([
                        'wire:click' => $action,
                        'x-bind:disabled' => $confirmText !== null ? 'typed !== '.\Illuminate\Support\Js::from($confirmText) : null,
                    ]));
                @endphp
                <x-ui.button :variant="$tone === 'danger' ? 'danger' : 'primary'" :loading="$action" :attributes="$confirmAttributes">
                    {{ $confirmLabel ?? __('Confirm') }}
                </x-ui.button>
            </div>
        </div>
    </div>
</x-ui.modal>
