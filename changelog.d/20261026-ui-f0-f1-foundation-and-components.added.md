Add the frontend foundation (F0) and the CampusOS UI component library (F1).

- Foundation: API client (`App\Api\CampusApi`) with token, institution, trace id and real browser IP/user agent on every call; typed API errors; encrypted-session sign-in state; sign-in, account-ready and permission middleware; friendly 403/404/419/429/500/503 pages with support reference; security headers (CSP with nonce); navigation built from `config/navigation.php` per role/permission; Indian number and timezone formatting.
- Design system: Inter (self-hosted), 125 Lucide icons copied into `resources/icons` (`npm run icons`), colour tokens with light/dark/system themes.
- Components (`resources/views/components/ui`): button, button-group, icon, badge, status-badge, alert, card, section, page-header, divider, spinner, skeleton, progress, empty-state, error-state, code, avatar, stat, description-list, timeline, time, field, input, password (show/hide + strength), textarea, select, combobox (search, multi), checkbox, radio-group (cards), switch, date-picker, date-range, otp-input, file-upload, dropdown, modal, confirm-dialog (typed confirmation), drawer, tooltip, popover, table (sortable, stacked on phones), pagination, filters bar, breadcrumbs, tabs, tab-link, stepper, command palette (Ctrl K), toasts, theme toggle.
- Layouts: document, auth, app shell (collapsible sidebar, top bar, institution switcher, user menu), error.
- Livewire helpers: `WithTable`, `WithApiErrors`, `InteractsWithApi`.
- Style guide at `/_ui` (local only).
- Config: `CAMPUSOS_API_URL`, `CAMPUSOS_API_TIMEOUT`, `CAMPUSOS_STYLE_GUIDE`, `SESSION_ENCRYPT=true`.
- Backend needs: `CAMPUSOS_TRUSTED_FRONTENDS` on the backend must include this server's IP.
