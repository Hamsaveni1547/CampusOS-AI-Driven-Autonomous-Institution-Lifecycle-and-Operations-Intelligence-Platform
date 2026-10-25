import type { AlpineStatic } from '../../../vendor/livewire/livewire/dist/livewire.esm';

/**
 * `x-data="tabs({ active, param })"` — accessible client-side tabs. When `param`
 * is set, the active tab is kept in the URL (?tab=...) so it survives reloads and links.
 */
export function registerTabs(Alpine: AlpineStatic): void {
    Alpine.data('tabs', (options: { active: string; param: string | null }) => ({
        active: options.param ? new URLSearchParams(window.location.search).get(options.param) ?? options.active : options.active,

        select(name: string): void {
            this.active = name;

            if (options.param) {
                const url = new URL(window.location.href);
                url.searchParams.set(options.param, name);
                window.history.replaceState(window.history.state, '', url);
            }
        },

        keydown(event: KeyboardEvent): void {
            const tabs = Array.from(this.$el.querySelectorAll<HTMLElement>('[role="tab"]'));
            const index = tabs.indexOf(document.activeElement as HTMLElement);

            if (index === -1 || !['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) {
                return;
            }

            event.preventDefault();
            const next = event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1 : (index + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
            tabs[next].focus();
            tabs[next].click();
        },
    }));
}
