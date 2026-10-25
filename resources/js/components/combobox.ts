import type { AlpineStatic } from '../../../vendor/livewire/livewire/dist/livewire.esm';

export interface ComboOption {
    value: string;
    label: string;
    description?: string | null;
}

interface ComboConfig {
    options: ComboOption[];
    value: string | string[] | null;
    multiple: boolean;
    placeholder: string;
}

/**
 * `x-data="combobox({...})"` — searchable select (single or multiple).
 * Works with `wire:model` through the hidden `<select>` it keeps in sync.
 */
export function registerCombobox(Alpine: AlpineStatic): void {
    Alpine.data('combobox', (config: ComboConfig) => ({
        options: config.options,
        multiple: config.multiple,
        placeholder: config.placeholder,
        selected: (Array.isArray(config.value) ? config.value : config.value ? [config.value] : []).map(String) as string[],
        query: '',
        open: false,
        active: 0,

        init(): void {
            const select = this.$refs.select as HTMLSelectElement | undefined;

            if (!select) {
                return;
            }

            const read = (): string[] => Array.from(select.selectedOptions).map((option) => option.value).filter((value) => value !== '');

            select.addEventListener('change', () => {
                this.selected = read();
            });

            // Pick up a value Livewire (wire:model) wrote into the select before Alpine started.
            this.$nextTick(() => {
                const current = read();

                if (current.length > 0) {
                    this.selected = current;
                } else if (this.selected.length > 0) {
                    this.sync();
                }
            });
        },

        get filtered(): ComboOption[] {
            const term = this.query.trim().toLowerCase();

            return term === ''
                ? this.options
                : this.options.filter((option) => `${option.label} ${option.description ?? ''}`.toLowerCase().includes(term));
        },

        get summary(): string {
            if (this.selected.length === 0) {
                return this.placeholder;
            }

            if (!this.multiple) {
                return this.labelFor(this.selected[0]);
            }

            return this.selected.length === 1 ? this.labelFor(this.selected[0]) : `${this.selected.length} selected`;
        },

        labelFor(value: string): string {
            return this.options.find((option) => option.value === value)?.label ?? value;
        },

        isSelected(value: string): boolean {
            return this.selected.includes(value);
        },

        openList(): void {
            this.open = true;
            this.active = 0;
            this.$nextTick(() => (this.$refs.search as HTMLInputElement | undefined)?.focus());
        },

        closeList(): void {
            this.open = false;
            this.query = '';
        },

        choose(option: ComboOption | undefined): void {
            if (!option) {
                return;
            }

            if (this.multiple) {
                this.selected = this.isSelected(option.value)
                    ? this.selected.filter((value) => value !== option.value)
                    : [...this.selected, option.value];
            } else {
                this.selected = [option.value];
                this.closeList();
                (this.$refs.button as HTMLElement | undefined)?.focus();
            }

            this.sync();
        },

        remove(value: string): void {
            this.selected = this.selected.filter((item) => item !== value);
            this.sync();
        },

        clear(): void {
            this.selected = [];
            this.sync();
        },

        move(step: number): void {
            const count = this.filtered.length;

            if (count > 0) {
                this.active = (this.active + step + count) % count;
            }
        },

        sync(): void {
            const select = this.$refs.select as HTMLSelectElement | undefined;

            if (!select) {
                return;
            }

            for (const option of Array.from(select.options)) {
                option.selected = this.selected.includes(option.value);
            }

            select.dispatchEvent(new Event('change', { bubbles: true }));
            select.dispatchEvent(new Event('input', { bubbles: true }));
        },
    }));
}
