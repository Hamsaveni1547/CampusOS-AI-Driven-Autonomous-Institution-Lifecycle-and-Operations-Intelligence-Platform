import type { Alpine as AlpineType } from './alpine';

declare global {
    interface Window {
        Alpine: AlpineType;
        Livewire: unknown;
    }
}

export {};
