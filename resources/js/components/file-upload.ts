import type { AlpineStatic } from '../../../vendor/livewire/livewire/dist/livewire.esm';

/**
 * `x-data="fileUpload({ maxKb, accept })"` — drag & drop zone with progress.
 * Uses Livewire's upload events (livewire-upload-start/progress/finish/error) from the inner file input.
 */
export function registerFileUpload(Alpine: AlpineStatic): void {
    Alpine.data('fileUpload', (options: { maxKb: number | null; accept: string | null }) => ({
        dragging: false,
        uploading: false,
        progress: 0,
        error: null as string | null,
        fileName: null as string | null,

        input(): HTMLInputElement | null {
            return this.$el.querySelector<HTMLInputElement>('input[type="file"]');
        },

        browse(): void {
            this.input()?.click();
        },

        drop(event: DragEvent): void {
            this.dragging = false;
            const files = event.dataTransfer?.files;
            const input = this.input();

            if (!files || files.length === 0 || !input) {
                return;
            }

            const transfer = new DataTransfer();
            Array.from(files).forEach((file) => transfer.items.add(file));
            input.files = transfer.files;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        },

        changed(): void {
            const file = this.input()?.files?.[0];
            this.error = null;
            this.fileName = file?.name ?? null;

            if (file && options.maxKb !== null && file.size > options.maxKb * 1024) {
                this.error = `The file is larger than ${Math.round(options.maxKb / 1024)} MB.`;
            }
        },

        start(): void {
            this.uploading = true;
            this.progress = 0;
        },

        advance(event: CustomEvent<{ progress: number }>): void {
            this.progress = event.detail.progress;
        },

        finish(): void {
            this.uploading = false;
            this.progress = 100;
        },

        fail(): void {
            this.uploading = false;
            this.error = 'The upload failed. Please try again.';
        },
    }));
}
