# GravityExport Lite — Happy Paths

> Source: https://www.gravitykit.com/docs/gravityexport/ (Lite-specific articles) + `readme.txt`, `gfexcel.php`, `src/` inspection
> Target: `gk-gravityexport-lite` (the free WordPress.org plugin — main file `gfexcel.php`, version 2.6.0)
> Environment: `@gravitykit/e2e-bootstrap` on `wpTestsPort` (see `tests/E2E/setup/playwright.config.js`); Gravity Forms is auto-provisioned by the bootstrap. Only `activation.spec.js` exists today.
> Reference plugin: `gravitykit-qa2/GravityView/tests/E2E/` for layout/helpers/CI conventions. The full GravityExport (Pro) happy-paths file at `gravitykit-qa/GravityExport/.claude/gravityexport-happy-paths.md` is the structural template — Lite is a subset.

Lite is a **single-feature plugin**: one secret download URL per form, optional admin-side configuration of which fields/values/labels appear, plus the ability to attach a per-entry export to a Gravity Forms notification. Everything Pro adds (Filter Sets, Save, Multi-row, PDF, OAuth/FTP storage, scheduling, webhooks, GravityView download button) is **out of scope** here and must not appear in this suite.

Priority is top-down: most visible, most-broken-if-it-breaks flows first. Each path is intended to become **one independent `.spec.js`** under `tests/E2E/tests/<domain>/<file>.spec.js`, mirroring GravityView's folder-per-feature layout.

---

## P0 — Core download URL (the entire baseline product)

### 1. Enable Download URL on a form
- **Role:** Admin
- **Preconditions:** Plugin active; a Gravity Forms form exists with ≥1 entry (seeded via fixtures API).
- **Steps:** Navigate to Form Settings → **GravityExport Lite**. Toggle **Enable download** on; save.
- **Expected:** Settings page reloads with the toggle persisted; a download URL (containing the form-scoped hash/secret) is rendered and copyable; visiting the URL while unauthenticated does not 404.
- **Location:** `tests/E2E/tests/download-url/enable-download-url.spec.js`
- **Docs:** "Getting started with GravityExport" article.

### 2. Download an export via the generated URL (Excel default)
- **Role:** Anonymous URL holder (no WP login)
- **Preconditions:** #1 completed; form has ≥1 entry.
- **Steps:** Issue an HTTP GET to the download URL with Playwright's `request` API (no auth, no cookies).
- **Expected:** `200`; `Content-Type` is `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`; `Content-Disposition` attachment filename ends `.xlsx`; body is non-empty binary that starts with the ZIP magic bytes `PK` (>512 bytes).
- **Location:** `tests/E2E/tests/download-url/download-xlsx.spec.js`

### 3. Change file extension via URL (`.csv`)
- **Role:** Anonymous URL holder
- **Preconditions:** #1 completed.
- **Steps:** Append/replace the extension on the download URL with `.csv` and GET it.
- **Expected:** `200`; `Content-Type` is `text/csv` (or `application/csv`); filename ends `.csv`; first line is a comma-separated header row matching the form's enabled fields; row count = entries + 1 (header).
- **Location:** `tests/E2E/tests/download-url/download-csv.spec.js`
- **Notes:** Lite supports Excel and CSV only. **Do not** test `.pdf` — that is Pro-only and must 404/redirect in Lite.

### 4. Regenerate / disable Download URL
- **Role:** Admin
- **Preconditions:** #1 completed; capture the current URL.
- **Steps (two assertions in one spec or two siblings):**
  - **Regenerate:** click the regenerate-secret control → save → confirm the URL hash changes; GET on the old URL is forbidden/404.
  - **Disable:** toggle Enable download off → save → GET on the URL is forbidden/404.
- **Expected:** New URL works; old URL is dead. Disable kills access entirely.
- **Location:** `tests/E2E/tests/download-url/regenerate-disable.spec.js`

---

## P1 — Field configuration on the form-scoped settings page

### 5. Enable / disable fields — disabled fields are excluded from the export
- **Role:** Admin
- **Preconditions:** Form with multiple field types; #1 completed.
- **Steps:** In Form Settings → GravityExport Lite, toggle one field off; save; download CSV.
- **Expected:** Disabled field is absent from the header row; remaining columns and row count are unchanged.
- **Location:** `tests/E2E/tests/fields/disable-field.spec.js`

### 6. Reorder fields — column order in export matches admin sort order
- **Role:** Admin
- **Preconditions:** Form with ≥3 enabled fields.
- **Steps:** Drag (or use the move-up/move-down controls) to reorder two fields; save; download CSV.
- **Expected:** Header columns appear in the new order; data rows align.
- **Location:** `tests/E2E/tests/fields/sort-order.spec.js`
- **Notes:** Prefer the keyboard/button-based reorder if drag-and-drop proves flaky under Playwright; verify in browser via MCP before locking in selectors.

---

## P2 — Access control

### 7. Restrict download to logged-in users
- **Role:** Admin (configures); Anonymous + Subscriber (verify)
- **Preconditions:** #1 completed.
- **Steps:** Toggle the "Only logged-in users can download" option on; save.
- **Expected:**
  - Anonymous GET on the download URL is denied (403 or login redirect; not a file).
  - Logged-in user (any role, since Lite gates on auth not capability) GET returns the export.
- **Location:** `tests/E2E/tests/permissions/logged-in-required.spec.js`
- **Docs:** "Restricting file access in GravityExport & GravityExport Lite".

---

## P3 — Data shaping (Lite-supported subset)

### 8. Custom column labels override field labels
- **Role:** Admin
- **Preconditions:** Form with at least one field that has a default label.
- **Steps:** In the GravityExport Lite settings, change a field's exported-column label; save; download CSV.
- **Expected:** Header row uses the custom label; data column under it is unchanged.
- **Location:** `tests/E2E/tests/labels-values/custom-labels.spec.js`
- **Docs:** "Changing labels in GravityExport".

### 9. Transpose mode — entries become columns, fields become rows
- **Role:** Admin
- **Preconditions:** Form with ≥2 entries and ≥2 enabled fields.
- **Steps:** Enable Transpose; save; download CSV.
- **Expected:** First column lists field labels (one per row); each subsequent column is one entry; total rows = fields + 1 metadata row (if any), total columns = entries + 1.
- **Location:** `tests/E2E/tests/data-shaping/transpose.spec.js`

### 10. Include entry notes column
- **Role:** Admin
- **Preconditions:** Form with at least one entry that has a note attached (seeded via fixtures API).
- **Steps:** Enable "Include entry notes"; save; download CSV.
- **Expected:** Header includes a Notes column; the row for the noted entry contains the note text (handles multiple notes joined per the plugin's convention — verify the exact format live before coding the assertion).
- **Location:** `tests/E2E/tests/data-shaping/entry-notes.spec.js`

---

## P4 — Multi-form report

### 11. Combine multiple forms into a single workbook
- **Role:** Admin
- **Preconditions:** Two forms with download URLs enabled and entries.
- **Steps:** Use the combined/bulk export entry point (verify the actual UI path via Playwright MCP — Lite docs reference this but the location moved between 2.x releases) to request a workbook covering both forms.
- **Expected:** `200`; `.xlsx` response; opening the workbook shows one sheet per form (or one sheet with form-named sections, whichever the implementation produces — confirm live).
- **Location:** `tests/E2E/tests/multi-form/combined-report.spec.js`
- **Notes:** This is the Lite-side equivalent of Pro's "bulk export all forms" path. If the UI for this lives behind a screen that turns out to be Pro-only, mark this path "Pro-only — drop from Lite suite" during Phase 2 verification and move on.

---

## P5 — Notification attachment (per-entry)

### 12. Attach single-entry export to a Gravity Forms notification
- **Role:** Admin (configures); form submitter (triggers)
- **Preconditions:** Form has a notification configured.
- **Steps:** In the notification editor, enable the GravityExport Lite "Attach export" option and choose **CSV** (one spec) or **XLSX** (sibling spec) — both formats are supported in Lite. Submit a new entry to trigger the notification.
- **Expected:** A captured outbound email (via MailHog if available in wp-env, else a tiny mu-plugin that short-circuits `wp_mail` and writes payloads to disk — same approach as Phase 2 decision #2 in the Pro file) has exactly one attachment, correct extension, correct MIME type, non-zero size, and content matches that single entry only.
- **Location:** `tests/E2E/tests/notifications/attach-csv.spec.js`, `tests/E2E/tests/notifications/attach-xlsx.spec.js`
- **Docs:** "Attaching an entry export to a notification using GravityExport Lite".

---

## P6 — Filtering / search via URL query parameters

### 13. Search filters on the download URL narrow the result set
- **Role:** Anonymous URL holder
- **Preconditions:** Form with ≥3 entries that differ on at least one filterable field (e.g., a "status" dropdown).
- **Steps:** GET the download URL with the plugin's supported query parameters appended (verify exact parameter names live via MCP — the readme refers to "search filters on the URL" but the precise param syntax must be confirmed against the current router code).
- **Expected:** `200`; CSV body contains only the matching entries; row count equals expected match count + 1 (header).
- **Location:** `tests/E2E/tests/download-url/search-filter.spec.js`

---

## Out of scope for this suite (explicitly)

These belong to Pro and must **not** be tested here. They live in the sibling `gravitykit-qa/GravityExport/.claude/gravityexport-happy-paths.md`:

- Filter Sets feed type (multi-feed, conditional logic, instant-download tab, date-range filters).
- Save addon (Local / Dropbox / FTP storage, single-entry triggers, export-on-update, reprocess, scheduling, webhooks).
- PDF export (Lite supports Excel and CSV only).
- Multi-row splitting for List/Multi-select fields.
- Splitting Name/Address complex fields into multiple columns.
- Checkbox-field-as-separate-columns (documented but not implemented even in Pro — Pro happy-paths file flags this as a Pro gap; do not test in Lite).
- GravityView "Add download button to a View" integration.
- Conditional logic with relative dates (Pro Filter Sets).
- Caching-plugin interactions, FastCron, HTTP Basic Auth — staging/plugin combinatorics, not Lite-scope.
- Developer hooks catalog and individual Field PHP class behavior — PHPUnit-scope, not Playwright.

---

## Reference plugin conventions to adopt (carried over from the Pro suite)

Observed in `gravitykit-qa2/GravityView/tests/E2E/` and `gravitykit-qa/GravityExport/tests/E2E/`:
- **One `.spec.js` per behavior**, grouped under a feature folder.
- **Helpers** via `@gravitykit/e2e-bootstrap` (`createHelpers`) and `@gravitykit/e2e-fixtures` (`api` for seeding forms, entries, notes, notifications, users).
- **Playwright config** is `createPlaywrightConfig({ setupDir, testDir })` with `baseURL` on `wpTestsPort` — **already correct** in `tests/E2E/setup/playwright.config.js` after commit `f1faf0f` (E2E port-mismatch fix). Do not regress that.
- **Role-based locators first** (`getByRole`, `getByLabel`); fall back to `getByText` / data-attributes (the existing `activation.spec.js` uses `[data-plugin*="gfexcel.php"]`) only when role queries are ambiguous.
- **Each test independent** — no shared mutable state across specs; seed via fixtures, clean up via teardown.
- **No retries to mask flake** — if a spec is flaky, fix the root cause (selector, timing, ordering) before moving on.

---

## Phase 2 decisions (resolved 2026-05-11)

1. **Mail capture** — wp-env does not ship MailHog. A tiny mu-plugin (`tests/E2E/setup/mu-plugins/e2e-mail-capture.php`) hooks `pre_wp_mail` to short-circuit `wp_mail`, copies attachments to `uploads/e2e-mail-capture/attachments/<sha1>-<filename>` so they survive `gform_after_email` cleanup, and exposes `GET/DELETE /wp-json/gk-e2e/v1/mail` (auth: same `X-E2E-TEST-TOKEN` header as e2e-fixtures).
2. **Combined multi-form report (#11) — DROPPED.** No admin UI in Lite 2.6; the multi-form story is a frontend shortcode (`[gravityexport_download_url id="1,2"]`) which overlaps too much with the Pro bulk-export and is too speculative for this pass.
3. **Search-filter query syntax (#13) — DROPPED.** `FilterRequest` reads `$_GET` filters but the exact parameter shape (per-field IDs, ranges, operators) is not stable from the public docs. Tabled for a follow-up pass after we ground-truth the syntax against the renderer.
4. **Field reorder (#6) — Implemented via feed meta.** The sortable JS keeps `_gform_setting_export-fields[enabled|disabled]` in sync from the DOM, so writing the hidden inputs from a Playwright `page.evaluate` is fragile (the JS resyncs from the UL on submit). We bypass that by writing `meta/export-fields` directly through `GravityExportAddon::save_feed_settings` (`patchExportFeedMeta` helper), which is what the addon would have stored anyway.
5. **Notes formatting (#10) — Implemented.** Notes appear as a single column ("Notes"); the spec asserts the column exists and contains our seeded note substring. Multi-note formatting is not exercised — only single-note attribution is verified.
6. **Custom column labels (#8) — DROPPED.** Lite ships only a global `use_admin_label` plugin setting; per-field custom-label override is a Pro-only UI. Documented here for completeness; not in the test suite.
7. **Settings-form submit gotcha** — the form has `data-js="page-loader"` which intercepts `<button>.click()` and submits without preserving the clicked button's name/value. The result is that `Regenerate URL` and `Disable download URL` clicks were being interpreted as plain `Save`. The `submitSettingsForm` helper bypasses this by calling `form.requestSubmit(button)` directly, which guarantees the addon action handler receives the right `gform-settings-save` value.
8. **Anonymous request isolation (#7)** — `request.newContext()` in Playwright can carry browser storage state in some configurations. Tests use Node's global `fetch` for guaranteed anonymous calls when the assertion depends on lack-of-auth.
9. **Suite parallelism** — Several specs mutate global WordPress state (rewrite rules, `gf_addon_feed`, the shared capture inbox). The bootstrap default `workers: '50%'` was observed to cause intermittent contention failures. `playwright.config.js` pins `workers: 1`; the full suite runs end-to-end in ~90s.

---

## Final delivered suite (2026-05-11)

**11 happy-path specs + the existing `activation.spec.js` = 14 specs total. Full suite runs 3× consecutively green in ~90s each.**

### Batch A — P0 download URL (4 specs)
- `tests/E2E/tests/download-url/enable-download-url.spec.js`
- `tests/E2E/tests/download-url/download-xlsx.spec.js`
- `tests/E2E/tests/download-url/download-csv.spec.js`
- `tests/E2E/tests/download-url/regenerate-disable.spec.js`

### Batch B — P1+P2 admin configuration (3 specs)
- `tests/E2E/tests/fields/disable-field.spec.js`
- `tests/E2E/tests/fields/sort-order.spec.js`
- `tests/E2E/tests/permissions/logged-in-required.spec.js`

### Batch C — P3+P5 data shaping + notifications (4 specs)
- `tests/E2E/tests/data-shaping/transpose.spec.js`
- `tests/E2E/tests/data-shaping/entry-notes.spec.js`
- `tests/E2E/tests/notifications/attach-csv.spec.js`
- `tests/E2E/tests/notifications/attach-xlsx.spec.js`

### Dropped (with rationale)
- **#8 Custom column labels** — Lite only ships the global `use_admin_label` toggle; no per-field custom-label UI to test.
- **#11 Combined multi-form report** — No Lite admin UI; the multi-form story is a frontend shortcode that overlaps with Pro bulk-export.
- **#13 Search filters on the URL** — Parameter syntax not ground-truthed; deferred to a follow-up.

### Supporting infrastructure
- `tests/E2E/helpers/test-helpers.js` — wraps `@gravitykit/e2e-bootstrap` `createHelpers`, re-derives the tests baseURL in each worker (the bootstrap's `api.initFromEnv()` requires `WP_ENV_URL` which is not in `.env`), and adds the Lite-specific helpers (`enableDownloadUrl`, `submitSettingsForm`, `patchExportFeedMeta`, `addNotification`, `submitEntryTriggeringNotifications`, `getCapturedEmails`, `readAttachmentBytes`, `parseCsv`).
- `tests/E2E/setup/mu-plugins/e2e-mail-capture.php` — mail capture mu-plugin, mounted via `additionalMappings` in `tests/E2E/setup/wp-env.config.js`.
- `tests/E2E/setup/playwright.config.js` — adds `workers: 1` over the bootstrap defaults.
