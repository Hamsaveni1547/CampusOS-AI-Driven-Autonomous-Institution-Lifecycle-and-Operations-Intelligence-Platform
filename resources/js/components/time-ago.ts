import type { AlpineStatic } from '../../../vendor/livewire/livewire/dist/livewire.esm';

const UNITS: [Intl.RelativeTimeFormatUnit, number][] = [
    ['year', 31536000],
    ['month', 2592000],
    ['week', 604800],
    ['day', 86400],
    ['hour', 3600],
    ['minute', 60],
];

export function relativeTime(date: Date, now: Date = new Date()): string {
    const seconds = Math.round((date.getTime() - now.getTime()) / 1000);
    const formatter = new Intl.RelativeTimeFormat(document.documentElement.lang || 'en', { numeric: 'auto' });

    for (const [unit, size] of UNITS) {
        if (Math.abs(seconds) >= size) {
            return formatter.format(Math.round(seconds / size), unit);
        }
    }

    return Math.abs(seconds) < 45 ? 'just now' : formatter.format(Math.round(seconds / 60), 'minute');
}

/**
 * `x-data="timeAgo('2026-10-26T10:00:00+05:30')"` — "5 minutes ago", refreshed every minute.
 */
export function registerTimeAgo(Alpine: AlpineStatic): void {
    Alpine.data('timeAgo', (iso: string) => ({
        label: '',
        timer: 0,

        init(): void {
            this.update();
            this.timer = window.setInterval(() => this.update(), 60000);
        },

        destroy(): void {
            window.clearInterval(this.timer);
        },

        update(): void {
            this.label = relativeTime(new Date(iso));
        },
    }));
}
