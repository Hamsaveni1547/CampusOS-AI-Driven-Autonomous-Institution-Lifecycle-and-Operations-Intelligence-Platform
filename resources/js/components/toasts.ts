import type { AlpineStatic, LivewireStatic } from '../../../vendor/livewire/livewire/dist/livewire.esm';

export type ToastType = 'success' | 'error' | 'warning' | 'info';

export interface ToastInput {
    type?: ToastType;
    title: string;
    message?: string | null;
    timeout?: number;
}

interface Toast extends Required<Omit<ToastInput, 'message'>> {
    id: number;
    message: string | null;
}

let sequence = 0;

/**
 * Store `$store.toasts`. Push from anywhere:
 *  - Alpine: `$store.toasts.push({ type: 'success', title: 'Saved' })`
 *  - Livewire (PHP): `$this->dispatch('toast', type: 'success', title: 'Saved')`
 *  - Server flash: rendered by the toast stack component on page load.
 */
export function registerToasts(Alpine: AlpineStatic, Livewire: LivewireStatic): void {
    Alpine.store('toasts', {
        items: [] as Toast[],

        push(input: ToastInput): void {
            const toast: Toast = {
                id: ++sequence,
                type: input.type ?? 'info',
                title: input.title,
                message: input.message ?? null,
                timeout: input.timeout ?? (input.type === 'error' ? 8000 : 4500),
            };

            this.items.push(toast);

            if (this.items.length > 5) {
                this.items.shift();
            }

            if (toast.timeout > 0) {
                window.setTimeout(() => this.dismiss(toast.id), toast.timeout);
            }
        },

        dismiss(id: number): void {
            this.items = this.items.filter((toast) => toast.id !== id);
        },
    });

    window.addEventListener('toast', (event) => {
        const detail = (event as CustomEvent<ToastInput | ToastInput[]>).detail;
        const input = Array.isArray(detail) ? detail[0] : detail;

        if (input?.title) {
            Alpine.store<{ push(input: ToastInput): void }>('toasts').push(input);
        }
    });

    Livewire.on('toast', (...args: unknown[]) => {
        const input = (Array.isArray(args[0]) ? args[0][0] : args[0]) as ToastInput | undefined;

        if (input?.title) {
            Alpine.store<{ push(input: ToastInput): void }>('toasts').push(input);
        }
    });
}
