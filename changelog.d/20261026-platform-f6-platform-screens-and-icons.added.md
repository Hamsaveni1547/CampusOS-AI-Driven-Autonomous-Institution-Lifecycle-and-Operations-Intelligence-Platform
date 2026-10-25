Add the platform administrator screens (F6) and load icons from the Lucide package.

- Institutions (`/platform/institutions`): search and filter, create an institution with its first administrator (invited by email), optional parent university, edit details, suspend with reason, re-activate.
- Platform settings (`/platform/settings`): platform-wide defaults per settings group, using the same screen as institution settings.
- Monitoring (`/platform/monitoring`): health checks, queue sizes, failed jobs with retry and remove.
- Icons now come straight from the `lucide-static` npm package (all ~2,100 Lucide icons, any name from lucide.dev works). The hand-picked copy in `resources/icons` and `npm run icons` are removed; `lucide-static` moved to `dependencies`, so deploys must run `npm ci`. Optional `CAMPUSOS_ICONS_PATH` points elsewhere if needed.
- Fixed two icons used by F5 screens that were not in the old copied list (`star`, `user-round-cog`).
