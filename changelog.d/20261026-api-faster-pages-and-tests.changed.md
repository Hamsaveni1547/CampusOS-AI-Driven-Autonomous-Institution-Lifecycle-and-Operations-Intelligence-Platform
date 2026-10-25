Faster pages, a list page fix and tests for every admin, academic, staff and platform screen.

- Short read cache in `CampusApi` (`CAMPUSOS_API_READ_CACHE_SECONDS`, default 20): dropdown lists are reused across pages, and every read during a Livewire round trip (opening a modal, sorting, paging back) is reused instead of re-fetched. Full page loads always read fresh data; any change made by the session clears its cache. Cached bodies are encrypted and kept per signed-in session and institution.
- `CampusApi::getMany()` runs several reads in parallel (user page, platform monitoring). `withoutCache()` keeps live data live (health, monitoring, import progress, notification bell).
- Fixed: the Users, Rooms, Imports, Staff and platform Institutions lists crashed because their results clashed with the table's page number.
- Tests: users and roles, institution profile, settings, templates, audit and export, imports, health, departments and heads, programmes, batches and sections, academic years and calendar, rooms, staff, qualifications and file download, designations, platform institutions, settings and monitoring, the read cache, and a check that every icon name used exists in Lucide.
