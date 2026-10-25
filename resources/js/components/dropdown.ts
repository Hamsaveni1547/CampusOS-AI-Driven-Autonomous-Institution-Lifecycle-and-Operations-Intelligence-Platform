import type { AlpineStatic } from '../../../vendor/livewire/livewire/dist/livewire.esm';

/**
 * `x-data="dropdown"` — menu button with keyboard support.
 * Markup: a `[x-ref=button]`, a `[x-ref=panel]` containing `[role=menuitem]` items.
 */
export function registerDropdown(Alpine: AlpineStatic): void {
    Alpine.data('dropdown', () => ({
        open: false,

        toggle(): void {
            this.open ? this.close() : this.show();
        },

        show(): void {
            this.open = true;
            this.$nextTick(() => this.items()[0]?.focus());
        },

        close(focusButton = true): void {
            if (!this.open) {
                return;
            }

            this.open = false;

            if (focusButton) {
                const trigger = this.$refs.button;
                (trigger?.querySelector<HTMLElement>('button, a, [tabindex]') ?? trigger)?.focus();
            }
        },

        items(): HTMLElement[] {
            return Array.from(this.$refs.panel?.querySelectorAll<HTMLElement>('[role="menuitem"]:not([disabled])') ?? []);
        },

        move(step: number): void {
            const items = this.items();

            if (items.length === 0) {
                return;
            }

            const index = items.indexOf(document.activeElement as HTMLElement);
            items[(index + step + items.length) % items.length].focus();
        },
    }));
}
