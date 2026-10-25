import type { AlpineStatic } from '../../../vendor/livewire/livewire/dist/livewire.esm';

export type ThemeMode = 'light' | 'dark' | 'system';

const COOKIE = 'campus_theme';

function systemPrefersDark(): boolean {
    return window.matchMedia('(prefers-color-scheme: dark)').matches;
}

export function applyTheme(mode: ThemeMode): void {
    const dark = mode === 'dark' || (mode === 'system' && systemPrefersDark());
    document.documentElement.classList.toggle('dark', dark);
}

function readMode(): ThemeMode {
    const match = document.cookie.match(new RegExp(`(?:^|; )${COOKIE}=(light|dark|system)`));

    return (match?.[1] as ThemeMode | undefined) ?? 'system';
}

/**
 * Store `$store.theme`: current mode and `set(mode)`. The choice is kept in a
 * cookie so the server can render the right class on the next page load.
 */
export function registerTheme(Alpine: AlpineStatic): void {
    Alpine.store('theme', {
        mode: readMode(),

        set(mode: ThemeMode): void {
            this.mode = mode;
            document.cookie = `${COOKIE}=${mode}; path=/; max-age=31536000; samesite=lax`;
            applyTheme(mode);
        },

        cycle(): void {
            const order: ThemeMode[] = ['light', 'dark', 'system'];
            this.set(order[(order.indexOf(this.mode) + 1) % order.length]);
        },
    });

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (readMode() === 'system') {
            applyTheme('system');
        }
    });
}
