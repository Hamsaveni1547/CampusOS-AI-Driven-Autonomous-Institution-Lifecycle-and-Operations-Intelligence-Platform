// Copies the icons CampusOS uses from lucide-static (MIT) into resources/icons.
// Run `npm run icons` after adding a name to the list. The copied SVGs are committed,
// so the app has no runtime dependency on the package.
import { copyFileSync, existsSync, mkdirSync, readdirSync, rmSync } from 'node:fs';
import { join } from 'node:path';

const icons = [
    // navigation & layout
    'house', 'layout-dashboard', 'menu', 'panel-left', 'panel-left-close', 'chevron-down', 'chevron-up', 'chevron-left',
    'chevron-right', 'chevrons-left', 'chevrons-right', 'chevrons-up-down', 'arrow-left', 'arrow-right', 'arrow-up',
    'arrow-down', 'arrow-up-down', 'external-link', 'ellipsis', 'ellipsis-vertical', 'grip-vertical',
    // actions
    'plus', 'pencil', 'trash-2', 'copy', 'check', 'x', 'search', 'filter', 'sliders-horizontal', 'download', 'upload',
    'refresh-cw', 'rotate-ccw', 'save', 'send', 'printer', 'eye', 'eye-off', 'log-in', 'log-out', 'link', 'share-2',
    'columns-3', 'list', 'layout-grid', 'maximize-2', 'minimize-2',
    // status & feedback
    'circle-check', 'circle-x', 'circle-alert', 'triangle-alert', 'info', 'circle-help', 'loader-circle', 'ban', 'clock',
    'history', 'bell', 'bell-off', 'inbox', 'sparkles',
    // people & security
    'user', 'users', 'user-plus', 'user-check', 'user-x', 'user-cog', 'contact', 'id-card', 'shield', 'shield-check',
    'shield-alert', 'key-round', 'lock', 'lock-open', 'fingerprint', 'smartphone', 'monitor', 'laptop', 'mail', 'phone',
    // academics & campus
    'building', 'building-2', 'school', 'graduation-cap', 'book-open', 'library', 'calendar', 'calendar-days',
    'calendar-check', 'calendar-plus', 'door-open', 'flask-conical', 'layers', 'network', 'briefcase', 'award',
    'clipboard-list', 'file-text', 'file-spreadsheet', 'file-up', 'files', 'folder', 'image', 'paperclip', 'map-pin',
    // system
    'settings', 'settings-2', 'activity', 'server', 'database', 'globe', 'palette', 'sun', 'moon', 'sun-moon',
    'languages', 'command', 'keyboard', 'scroll-text', 'chart-column', 'gauge', 'toggle-right', 'qr-code',
];

const source = 'node_modules/lucide-static/icons';
const target = 'resources/icons';

if (existsSync(target)) {
    for (const file of readdirSync(target)) {
        rmSync(join(target, file));
    }
} else {
    mkdirSync(target, { recursive: true });
}

const missing = [];

for (const name of icons) {
    const from = join(source, `${name}.svg`);

    if (!existsSync(from)) {
        missing.push(name);
        continue;
    }

    copyFileSync(from, join(target, `${name}.svg`));
}

console.log(`Copied ${icons.length - missing.length} icons to ${target}.`);

if (missing.length > 0) {
    console.error(`Missing in lucide-static: ${missing.join(', ')}`);
    process.exit(1);
}
