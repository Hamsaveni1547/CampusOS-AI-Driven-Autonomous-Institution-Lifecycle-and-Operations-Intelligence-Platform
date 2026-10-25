import type { AlpineStatic } from '../../../vendor/livewire/livewire/dist/livewire.esm';

/**
 * `x-data="autoTextarea({ max })"` — grows with its content and counts characters.
 */
export function registerTextarea(Alpine: AlpineStatic): void {
    Alpine.data('autoTextarea', (options: { max: number | null }) => ({
        count: 0,
        max: options.max,

        init(): void {
            this.$nextTick(() => this.resize());
        },

        resize(): void {
            const area = this.$refs.area as HTMLTextAreaElement | undefined;

            if (!area) {
                return;
            }

            this.count = area.value.length;
            area.style.height = 'auto';
            area.style.height = `${area.scrollHeight + 2}px`;
        },
    }));
}
