import type { AlpineStatic } from '../../../vendor/livewire/livewire/dist/livewire.esm';

/**
 * `x-data="dialog({ name, open })"` — modal / drawer / confirm dialog.
 * Open from anywhere: `$dispatch('open-dialog', 'name')`; close: `$dispatch('close-dialog', 'name')`.
 * From Livewire: `$this->dispatch('open-dialog', 'name')`. Focus is trapped with Alpine's x-trap.
 */
export function registerDialog(Alpine: AlpineStatic): void {
    Alpine.data('dialog', (options: { name: string; open?: boolean; dismissible?: boolean }) => ({
        name: options.name,
        open: options.open ?? false,
        dismissible: options.dismissible ?? true,

        show(): void {
            this.open = true;
            document.body.classList.add('overflow-hidden');
        },

        close(): void {
            this.open = false;
            document.body.classList.remove('overflow-hidden');
            this.$dispatch('dialog-closed', this.name);
        },

        dismiss(): void {
            if (this.dismissible) {
                this.close();
            }
        },

        handleOpen(event: CustomEvent<string | string[] | { name: string }>): void {
            if (this.matches(event.detail)) {
                this.show();
            }
        },

        handleClose(event: CustomEvent<string | string[] | { name: string }>): void {
            if (this.matches(event.detail)) {
                this.close();
            }
        },

        matches(detail: string | string[] | { name: string }): boolean {
            const name = Array.isArray(detail) ? detail[0] : typeof detail === 'object' ? detail.name : detail;

            return name === this.name;
        },
    }));
}
