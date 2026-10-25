/**
 * CampusOS frontend entry. Livewire's ESM bundle provides Alpine (with focus,
 * anchor, collapse and persist plugins); CampusOS registers its UI behaviour
 * as named Alpine components and stores before starting Livewire.
 */
import { Alpine, Livewire } from '../../vendor/livewire/livewire/dist/livewire.esm';

import { registerTheme } from './components/theme';
import { registerToasts } from './components/toasts';
import { registerDropdown } from './components/dropdown';
import { registerDialog } from './components/dialog';
import { registerCombobox } from './components/combobox';
import { registerOtpInput } from './components/otp-input';
import { registerFileUpload } from './components/file-upload';
import { registerPasswordStrength } from './components/password-strength';
import { registerCommandPalette } from './components/command-palette';
import { registerTabs } from './components/tabs';
import { registerClipboard } from './components/clipboard';
import { registerSidebar } from './components/sidebar';
import { registerTextarea } from './components/textarea';
import { registerTimeAgo } from './components/time-ago';

registerTheme(Alpine);
registerToasts(Alpine, Livewire);
registerDropdown(Alpine);
registerDialog(Alpine);
registerCombobox(Alpine);
registerOtpInput(Alpine);
registerFileUpload(Alpine);
registerPasswordStrength(Alpine);
registerCommandPalette(Alpine);
registerTabs(Alpine);
registerClipboard(Alpine);
registerSidebar(Alpine);
registerTextarea(Alpine);
registerTimeAgo(Alpine);

window.Alpine = Alpine;

Livewire.start();
