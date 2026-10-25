{{--
    Global toast area (rendered once by the document layout). Shows:
    - server flash: session('toast') = ['type' => 'success', 'title' => '...', 'message' => '...']
    - Livewire: $this->dispatch('toast', type: 'success', title: 'Saved')
    - Alpine: $store.toasts.push({ type, title, message })
--}}
@php
    $flash = session('toast');
    $flash = is_array($flash) ? [$flash] : [];
    if (session('status')) {
        $flash[] = ['type' => 'info', 'title' => session('status')];
    }
@endphp
<div
    x-data
    x-init="@js($flash).forEach((toast) => $store.toasts.push(toast))"
    aria-live="polite"
    aria-atomic="false"
    class="pointer-events-none fixed inset-x-0 bottom-0 z-70 flex flex-col items-center gap-2 p-4 sm:inset-x-auto sm:right-0 sm:top-0 sm:bottom-auto sm:items-end sm:p-6"
>
    <template x-for="toast in $store.toasts.items" :key="toast.id">
        <div
            x-transition:enter="transition duration-200 ease-out"
            x-transition:enter-start="translate-y-2 opacity-0 sm:translate-x-4 sm:translate-y-0"
            x-transition:enter-end="translate-x-0 translate-y-0 opacity-100"
            x-transition:leave="transition duration-150 ease-in"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="pointer-events-auto flex w-full max-w-sm gap-3 rounded-lg border border-line bg-surface-raised p-3.5 shadow-lg"
            :role="toast.type === 'error' ? 'alert' : 'status'"
        >
            <span class="mt-0.5" :class="{
                'text-success': toast.type === 'success',
                'text-danger': toast.type === 'error',
                'text-warning': toast.type === 'warning',
                'text-info': toast.type === 'info',
            }">
                <template x-if="toast.type === 'success'">{!! \App\Support\Icons::svg('circle-check', 'size-5') !!}</template>
                <template x-if="toast.type === 'error'">{!! \App\Support\Icons::svg('circle-x', 'size-5') !!}</template>
                <template x-if="toast.type === 'warning'">{!! \App\Support\Icons::svg('triangle-alert', 'size-5') !!}</template>
                <template x-if="toast.type === 'info'">{!! \App\Support\Icons::svg('info', 'size-5') !!}</template>
            </span>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-ink" x-text="toast.title"></p>
                <p class="mt-0.5 text-sm text-ink-muted" x-show="toast.message" x-text="toast.message"></p>
            </div>
            <button type="button" class="-m-1 h-fit rounded-md p-1 text-ink-subtle hover:text-ink" x-on:click="$store.toasts.dismiss(toast.id)" aria-label="{{ __('Dismiss') }}">
                {!! \App\Support\Icons::svg('x', 'size-4') !!}
            </button>
        </div>
    </template>
</div>
