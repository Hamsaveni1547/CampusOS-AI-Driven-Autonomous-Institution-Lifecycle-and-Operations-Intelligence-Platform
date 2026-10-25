import type { AlpineStatic } from '../../../vendor/livewire/livewire/dist/livewire.esm';

const KEY = 'campus.sidebar.collapsed';

/**
 * `x-data="sidebar"` on the app shell — desktop collapse (remembered) and mobile drawer.
 */
export function registerSidebar(Alpine: AlpineStatic): void {
    Alpine.data('sidebar', () => ({
        collapsed: window.localStorage.getItem(KEY) === '1',
        mobileOpen: false,

        toggleCollapsed(): void {
            this.collapsed = !this.collapsed;
            window.localStorage.setItem(KEY, this.collapsed ? '1' : '0');
        },

        openMobile(): void {
            this.mobileOpen = true;
        },

        closeMobile(): void {
            this.mobileOpen = false;
        },
    }));
}
