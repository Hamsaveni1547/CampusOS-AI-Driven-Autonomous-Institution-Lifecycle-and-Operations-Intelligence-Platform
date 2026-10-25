import type { AlpineStatic } from '../../../vendor/livewire/livewire/dist/livewire.esm';

export interface PaletteItem {
    label: string;
    section: string;
    url: string;
    keywords?: string;
}

/**
 * `x-data="commandPalette({ items })"` — Ctrl/⌘+K quick navigation over the pages the user can open.
 */
export function registerCommandPalette(Alpine: AlpineStatic): void {
    Alpine.data('commandPalette', (options: { items: PaletteItem[] }) => ({
        open: false,
        query: '',
        active: 0,
        items: options.items,

        get results(): PaletteItem[] {
            const term = this.query.trim().toLowerCase();
            const list = term === ''
                ? this.items
                : this.items.filter((item) => `${item.label} ${item.section} ${item.keywords ?? ''}`.toLowerCase().includes(term));

            return list.slice(0, 12);
        },

        toggle(): void {
            this.open ? this.close() : this.show();
        },

        show(): void {
            this.open = true;
            this.query = '';
            this.active = 0;
            this.$nextTick(() => (this.$refs.search as HTMLInputElement | undefined)?.focus());
        },

        close(): void {
            this.open = false;
        },

        move(step: number): void {
            const count = this.results.length;

            if (count > 0) {
                this.active = (this.active + step + count) % count;
            }
        },

        go(item: PaletteItem | undefined): void {
            if (item) {
                window.location.href = item.url;
            }
        },

        shortcut(event: KeyboardEvent): void {
            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
                event.preventDefault();
                this.toggle();
            }
        },
    }));
}
