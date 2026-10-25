Add home, notifications, calendar and My account screens (F3).

- Screens: `/` home (current term, two-step reminder, latest notifications, next 30 days, shortcuts), `/notifications` (all/unread, mark read, load more), `/calendar` (month grid + agenda, type filter), `/account` (photo, name, language, timezone), `/account/security` (two-step methods add/remove/default, recovery codes, password change, signed-in devices, remembered devices, recent activity), `/account/notifications` (in-app/email per category), `/account/staff-record` (read-only HR record).
- Notification bell with unread count in the top bar (refreshes every minute).
- Fixed: dev-server Content Security Policy errors — Vite now runs on 127.0.0.1:5173 (browsers reject `[::1]` in CSP). Restart `npm run dev` after pulling.
