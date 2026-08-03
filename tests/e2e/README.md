# End-to-end tests

Exercises the consent gate, the export activation event, and outbound link tagging against a real WordPress install.

## Why a probe mu-plugin

Playwright cannot see a server-side `wp_remote_post`, so it cannot answer the question that matters most here: *did anything get transmitted before consent?* `mu-plugin/gk-analytics-probe.php` intercepts analytics requests at `pre_http_request`, records the payload, and short-circuits them, so the suite can assert on what the plugin **tried** to send while nothing leaves the machine.

## Running

The site is minted by Siteminter, so its port varies and is passed in rather than hardcoded.

```bash
# 1. Mint a site with Gravity Forms and this plugin.
cd <siteminter>
npm run cli -- mint --name=gexplit-19 \
  --plugins=<plugins>/gravityforms,<plugins>/gf-entries-in-excel

# 2. Install the probe and flush rewrites (the download route needs them).
cp <plugin>/tests/e2e/mu-plugin/gk-analytics-probe.php <siteminter>/gexplit-19-*/mu-plugins/
cd <siteminter>/gexplit-19-* && <siteminter>/node_modules/.bin/wp-env run cli wp rewrite flush --hard

# 3. Run.
cd <plugin>/tests/e2e
npm install && npx playwright install chromium
GK_E2E_URL=http://localhost:<port> \
GK_E2E_SITEMINTER=<siteminter> \
GK_E2E_SITE_DIR=<siteminter>/gexplit-19-<hash> \
  npm test
```

`globalSetup` seeds the fixture through wp-cli (a form, two entries, an enabled export feed), logs in as admin once, and resets the probe.

## What is covered

**Consent gate** — nothing transmits before consent; the prompt states what is and is not collected; declining is durable and stays silent; granting records a hash of the exact wording shown; opting in emits `analytics_opt_in` and nothing else.

**Export activation** — an anonymous download with no consent transmits nothing; a completed download emits `export_completed` with the registered props; the payload carries no entry data, email, raw site address or the secret download hash; `site_id` is a 64-hex HMAC and is the `site` group key; repeat same-day downloads count once; self-declared crawlers are not counted.

**Outbound links** — the row links are tagged; "Documentation" reaches documentation rather than the product page; the upgrade link carries the upgrade campaign; no link carries a per-install identifier; every link resolves 200 with its tags intact.

## A caution about vacuous tests

The first version of "nothing is transmitted before consent" only browsed wp-admin. It passed with the consent gate deliberately removed, because browsing admin reaches no emitter — it proved nothing. It now performs an export download, which does emit, and fails when the gate is broken.

Tamper-test any assertion here before trusting it: break the thing it guards and confirm it goes red.

## Fixture seeding note

The router resolves a download by matching the hash in **feed** meta, while `GFExcel::url()` falls back to form meta. A hash written only to form meta produces a working-looking URL that then 404s, so `support/seed.php` writes it to the feed.
