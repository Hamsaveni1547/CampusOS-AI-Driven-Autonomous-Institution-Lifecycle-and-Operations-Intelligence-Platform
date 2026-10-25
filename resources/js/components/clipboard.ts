import type { AlpineStatic } from '../../../vendor/livewire/livewire/dist/livewire.esm';

/**
 * `x-data="clipboard('text')"` — copy button with a short "Copied" state.
 */
export function registerClipboard(Alpine: AlpineStatic): void {
    Alpine.data('clipboard', (text: string) => ({
        copied: false,

        async copy(): Promise<void> {
            try {
                await navigator.clipboard.writeText(text);
                this.copied = true;
                window.setTimeout(() => (this.copied = false), 1600);
            } catch {
                this.$dispatch('toast', { type: 'error', title: 'Could not copy to the clipboard' });
            }
        },
    }));
}
