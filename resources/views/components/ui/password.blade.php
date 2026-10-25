{{--
    Password with show/hide toggle; strength="10" adds the live rules checklist (min length 10).
    <x-ui.password label="New password" wire:model="password" strength="10" autocomplete="new-password" />
--}}
@props(['label' => null, 'hint' => null, 'error' => null, 'name' => null, 'strength' => null])
<div @if ($strength) x-data="passwordStrength({ minLength: {{ (int) $strength }} })" @endif {{ $attributes->only('class') }}>
    <div x-data="{ visible: false }" @if ($strength) x-on:input="track($event)" @endif>
        <x-ui.input :label="$label" :hint="$hint" :error="$error" :name="$name" type="password" x-bind:type="visible ? 'text' : 'password'" {{ $attributes->except('class') }}>
            <x-slot:trailing>
                <button type="button" x-on:click="visible = ! visible" class="rounded-md p-1.5 text-ink-subtle hover:text-ink"
                    :aria-label="visible ? @js(__('Hide password')) : @js(__('Show password'))" :aria-pressed="visible">
                    <span x-show="! visible">{!! \App\Support\Icons::svg('eye', 'size-4') !!}</span>
                    <span x-show="visible" x-cloak>{!! \App\Support\Icons::svg('eye-off', 'size-4') !!}</span>
                </button>
            </x-slot:trailing>
        </x-ui.input>
    </div>

    @if ($strength)
        <div class="mt-2.5 space-y-2" x-show="value !== ''" x-cloak>
            <div class="flex gap-1" aria-hidden="true">
                <template x-for="index in 4" :key="index">
                    <span class="h-1 flex-1 rounded-full transition" :class="index <= passed
                        ? (level === 'strong' ? 'bg-success' : level === 'fair' ? 'bg-warning' : 'bg-danger')
                        : 'bg-surface-muted'"></span>
                </template>
            </div>
            <ul class="grid gap-1 text-xs sm:grid-cols-2">
                <template x-for="rule in rules()" :key="rule.label">
                    <li class="flex items-center gap-1.5" :class="rule.test(value) ? 'text-success' : 'text-ink-muted'">
                        <span x-show="rule.test(value)">{!! \App\Support\Icons::svg('circle-check', 'size-3.5') !!}</span>
                        <span x-show="! rule.test(value)">{!! \App\Support\Icons::svg('circle-x', 'size-3.5 opacity-50') !!}</span>
                        <span x-text="rule.label"></span>
                    </li>
                </template>
            </ul>
        </div>
    @endif
</div>
