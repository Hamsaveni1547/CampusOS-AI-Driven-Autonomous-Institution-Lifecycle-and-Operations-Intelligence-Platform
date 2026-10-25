Add the administration screens (F4) and the academic structure and staff screens (F5).

- Users (`/admin/users`): search and filter by role, membership and lock state; add a user with roles, by invitation or with a one-time temporary password. User page: edit details, assign roles (optionally per department, with an end date), revoke roles, unlock, reset password (email link or temporary password), reset two-step verification, resend invitation, deactivate with reason or re-activate, and recent security activity.
- Roles & permissions (`/admin/roles`): built-in roles read only; custom roles with a permission matrix grouped by module (tick all/none per module), department-scoped flag, active switch, delete when nobody holds the role.
- Institution profile (`/admin/institution`): identity, logo upload, contact, address, time zone and currency.
- Settings (`/admin/settings`): one tab per settings group; fields are built from the API (on/off, number, text, list, secrets kept hidden).
- Notification templates (`/admin/templates`): edit the subject and body of each email, see allowed placeholders, reset to the built-in wording; email delivery log with status filter.
- Audit log (`/admin/audit`): record changes and security events with filters and date range, full details per entry, CSV export.
- Imports (`/admin/imports`): upload a CSV of users or staff, see required columns and a blank template, live progress, per-row problems, commit or cancel.
- System health (`/admin/health`): database, cache, storage, queue and scheduler status, refreshed every 30 seconds.
- Departments (`/departments`): add/edit/delete, assign or remove the head of department.
- Programmes (`/programmes`): level, duration, terms, intake and regulation per department.
- Batches & sections (`/batches`): create a batch with its sections, rename or change status, add/edit/remove sections with capacity and class advisor.
- Academic years (`/academic-years`): years with their terms, make current, close, delete; holidays and events calendar by month.
- Rooms (`/rooms`): type, seats, building, floor and owning department.
- Staff (`/staff`): list with filters, add staff (with invitation), staff page with job and personal details, status changes (leave, exit), qualifications with certificate upload and download.
- Designations (`/designations`): teaching and non-teaching titles with rank.
- File downloads go through the frontend (`/files/{id}/download`), so the API token never reaches the browser.
