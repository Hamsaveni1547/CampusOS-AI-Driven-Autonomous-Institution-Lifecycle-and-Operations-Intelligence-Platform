/**
 * Minimal Alpine typing (Alpine is bundled by Livewire, so @types/alpinejs is not installed).
 */
export interface Alpine {
    data(name: string, callback: (...args: unknown[]) => Record<string, unknown>): void;
    store(name: string, value?: unknown): unknown;
}
