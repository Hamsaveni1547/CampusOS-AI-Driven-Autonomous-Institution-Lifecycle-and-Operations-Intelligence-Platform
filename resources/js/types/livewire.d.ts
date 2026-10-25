/**
 * Minimal typings for the Livewire ESM bundle (which also ships Alpine and its plugins).
 */
declare module '*/livewire.esm' {
    export interface AlpineMagics {
        $el: HTMLElement;
        $refs: Record<string, HTMLElement>;
        $dispatch(event: string, detail?: unknown): void;
        $nextTick(callback?: () => void): Promise<void>;
        $watch<T>(property: string, callback: (value: T, oldValue: T) => void): void;
        $wire: LivewireComponent | undefined;
        $id(name: string): string;
    }

    export interface LivewireComponent {
        get(property: string): unknown;
        set(property: string, value: unknown, live?: boolean): Promise<void>;
        call(method: string, ...params: unknown[]): Promise<unknown>;
        entangle(property: string, live?: boolean): unknown;
        [key: string]: unknown;
    }

    export interface AlpineStatic {
        data<T extends object>(name: string, callback: (...args: never[]) => T & ThisType<T & AlpineMagics>): void;
        store<T extends object>(name: string, value: T): void;
        store<T = unknown>(name: string): T;
        start(): void;
    }

    export interface LivewireStatic {
        start(): void;
        on(event: string, callback: (...args: unknown[]) => void): () => void;
        hook(name: string, callback: (...args: unknown[]) => void): void;
    }

    export const Alpine: AlpineStatic;
    export const Livewire: LivewireStatic;
}

interface Window {
    Alpine: import('*/livewire.esm').AlpineStatic;
}
