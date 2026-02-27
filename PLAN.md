# AYN Thor Ship Date Estimator — Project Plan

## Overview

A single-purpose shipping estimate tool for AYN Thor handhelds. Users pick their model variant, enter the first 4 digits of their order number, and get an interpolated/extrapolated ship date. Users can subscribe (with email verification) to get notified when their estimate changes. A scraper runs daily against the AYN shipping dashboard and alerts the admin if anything goes wrong.

**Deploy target:** Ploi.io (standard Laravel deployment)
**Domain:** TBD (e.g., shipwhen.com, ordereta.com — whatever is available)

---

## Tech Stack

- **Backend:** Laravel 11 (PHP 8.3+)
- **Database:** SQLite (`database/database.sqlite`)
- **Frontend:** Blade layouts + Inertia.js with React
- **Styling:** Tailwind CSS
- **Scraping:** `spatie/browsershot` (Puppeteer) for JS-rendered Shopify page, with fallback to `symfony/dom-crawler`
- **Queue:** SQLite-backed (`database` driver) for emails
- **Mail:** Laravel Mail via SMTP (Postmark, Mailgun, or Resend)
- **Scheduler:** Laravel scheduler via Ploi cron

---

## Database Schema

### `model_variants`

```
id            — bigint, primary key
name          — varchar(255) (e.g., "Rainbow Max")
slug          — varchar(100), unique (e.g., "rainbow-max")
display_order — integer, default 0
color_config  — json (border, accent, tagBg — used by frontend for button styling)
created_at    — timestamp
updated_at    — timestamp
```

### `shipping_batches`

Each row = one data point from the shipping dashboard.

```
id               — bigint, primary key
model_variant_id — bigint, foreign key → model_variants.id
ship_date        — date
order_range_start— integer (first 4 digits)
order_range_end  — integer (first 4 digits)
scraped_at       — timestamp
created_at       — timestamp
updated_at       — timestamp

unique index: (model_variant_id, ship_date, order_range_start)
```

### `subscribers`

```
id                  — bigint, primary key
email               — varchar(255)
model_variant_id    — bigint, foreign key → model_variants.id
order_prefix        — integer (first 4 digits, e.g., 1350)
last_estimated_date — date, nullable
email_verified_at   — timestamp, nullable
verification_token  — varchar(64), unique
unsubscribe_token   — varchar(64), unique
created_at          — timestamp
updated_at          — timestamp

unique index: (email, model_variant_id, order_prefix)
```

### `scrape_logs`

Track every scrape attempt so we can detect failures.

```
id             — bigint, primary key
status         — enum('success', 'failed')
records_found  — integer, default 0
records_new    — integer, default 0
error_message  — text, nullable
duration_ms    — integer, nullable
created_at     — timestamp
```

---

## Routes

### Pages

```
GET  /                    — Calculator page (this IS the homepage)
GET  /verify/{token}      — Email verification handler → redirects to / with ?verified=1
GET  /unsubscribe/{token} — Unsubscribe handler → shows confirmation page
```

### API (called by the React frontend)

```
GET  /api/data            — All model variants + shipping batch data
POST /api/subscribe       — Subscribe to notifications
```

There is no admin panel. Scrape failures are sent to the admin via email. Data is managed via artisan commands.

---

## Artisan Commands

### `php artisan scrape`

1. Fetch `https://www.ayntec.com/pages/shipment-dashboard` via Browsershot.
2. Parse the HTML using the parser (see Scraper section).
3. Upsert results into `shipping_batches`.
4. Log result to `scrape_logs`.
5. If the scrape fails OR finds 0 records → send alert email to admin (`ADMIN_EMAIL` in `.env`).
6. If the scrape succeeds but finds fewer records than the previous successful scrape → also alert (data may have been removed/restructured).

### `php artisan notify`

1. For each verified subscriber: calculate estimated ship date using current batch data.
2. If the estimate differs from `last_estimated_date` by ≥ 2 days, queue a notification email.
3. Update `last_estimated_date`.

### `php artisan seed:data`

Seed all model variants and the historical shipping data hardcoded from the known dataset (listed below).

---

## Scheduler

```php
Schedule::command('scrape')->dailyAt('06:00')->withoutOverlapping();
Schedule::command('scrape')->dailyAt('18:00')->withoutOverlapping();
Schedule::command('notify')->dailyAt('06:30');
Schedule::command('notify')->dailyAt('18:30');
```

---

## Scraper

### `App\Services\ScraperService`

```php
class ScraperService
{
    public function run(): ScrapeResult
    {
        $startTime = microtime(true);

        try {
            $html = Browsershot::url('https://www.ayntec.com/pages/shipment-dashboard')
                ->waitUntilNetworkIdle()
                ->bodyHtml();

            $records = $this->parse($html);
            $new = $this->upsert($records);

            return new ScrapeResult(
                status: 'success',
                recordsFound: count($records),
                recordsNew: $new,
                durationMs: $this->elapsed($startTime),
            );
        } catch (\Throwable $e) {
            return new ScrapeResult(
                status: 'failed',
                error: $e->getMessage(),
                durationMs: $this->elapsed($startTime),
            );
        }
    }
}
```

### Parsing logic

The AYN shipping page contains blocks formatted like:

```
2026/1/15
AYN Thor Rainbow Max: 1062xx--1114xx
AYN Thor Rainbow Pro: 1050xx--1109xx
```

The parser should:
1. Find all date headings matching pattern `\d{4}/\d{1,2}/\d{1,2}`.
2. For each date, find subsequent lines matching `AYN Thor (.+?):\s*(\d{4})xx--(\d{4})xx`.
3. Map the captured model name to a `model_variant` slug.
4. Return structured records for upserting.

### Upsert strategy

Use the unique index `(model_variant_id, ship_date, order_range_start)` to prevent duplicates. On conflict, update `order_range_end` and `scraped_at` (in case they correct data).

### Admin alerts

Send an email to `ADMIN_EMAIL` when:
- Scrape throws an exception (network error, Browsershot crash, etc.)
- Scrape returns 0 parsed records (page structure may have changed)
- Record count is significantly lower than last successful scrape (data disappeared)

The alert email should include: timestamp, error message (if any), records found vs expected, and the raw HTML snippet (first 2000 chars) for debugging.

---

## Estimation Logic

### `App\Services\EstimationService`

Identical logic to the React calculator already built. For a given model variant and 4-digit order prefix:

1. Get all shipping batches for the variant, ordered by `order_range_end` ascending, deduplicated to get cumulative end points per date.
2. If `orderPrefix <= first.order_range_end` → return `{ type: "shipped", date: first.ship_date }`.
3. Walk through consecutive data points. If orderPrefix falls between `point[i-1].order_range_end` and `point[i].order_range_end` → linear interpolation between their dates. Return `{ type: "estimated", date: interpolated }`.
4. If orderPrefix > last data point → linear extrapolation from last two points. Return `{ type: "extrapolated", date: extrapolated }`.

This PHP version is used by the `notify` command to detect estimate changes. The React frontend keeps its own client-side copy for instant results without API calls.

**Important:** Both implementations must produce identical results. Write a test that feeds the same inputs to both and compares.

---

## Email Subscription Flow

### Subscribe

1. User fills in email, model variant is pre-selected, order prefix is pre-filled from their input.
2. `POST /api/subscribe` — validate, check bot detection, create subscriber with `email_verified_at = null`.
3. Generate `verification_token` and `unsubscribe_token` via `Str::random(64)`.
4. Queue verification email with link: `{APP_URL}/verify/{token}`.

### Bot detection

Three layers, no third-party CAPTCHA:

1. **Honeypot:** Hidden field `website` (CSS `position:absolute; left:-9999px; opacity:0; tabindex:-1; autocomplete:off; aria-hidden:true`). Reject if not empty.
2. **Signed timestamp:** Hidden field `_ts` containing `{timestamp}.{hmac}`. Reject if elapsed < 3 seconds or > 1 hour, or if HMAC doesn't match.
3. **Rate limiting:** 5 subscribe requests per IP per hour via Laravel throttle middleware.

### Verify

1. `GET /verify/{token}` → find subscriber by token, set `email_verified_at = now()`.
2. Redirect to `/?verified=1`. Frontend shows a success toast.

### Notify

1. `notify` command recalculates estimates for all verified subscribers.
2. If estimate changed by ≥ 2 days from `last_estimated_date`, queue notification email.
3. Email contains: new date, old date, model name, link to site, one-click unsubscribe.
4. Update `last_estimated_date`.

### Unsubscribe

1. `GET /unsubscribe/{token}` → delete subscriber.
2. Show simple "You've been unsubscribed" page.

---

## Email Templates

### Verification email
- Subject: `Verify your shipping estimate subscription`
- Body: "Click below to verify your email. You'll be notified when the estimated ship date for your AYN Thor {Model} order #{prefix}xx changes."
- CTA button: Verify link

### Estimate changed email
- Subject: `Your AYN Thor {Model} estimate changed`
- Body: "The estimated ship date for your order #{prefix}xx ({Model}) has changed from {old_date} to {new_date}."
- CTA button: "View your estimate" → link to site
- Footer: one-click unsubscribe link

### Scrape failure alert (admin only)
- Subject: `[ALERT] AYN shipping scrape failed`
- Body: timestamp, error details, records found, HTML snippet for debugging
- Sent to `ADMIN_EMAIL` env var

All emails have both HTML and plain text versions.

---

## Frontend

### Design

Port the React calculator already created. Same design language throughout:

- **Background:** `#0c0c14`
- **Card:** `#15151f`, border `#2a2a3a`, rounded 16px
- **Input:** `#1a1a28`, border `#2a2a3a`
- **Text:** `#e0e0e8` primary, `#555570` muted
- **Shipped:** `#4ade80` green
- **Estimated:** `#818cf8` purple
- **Extrapolated:** `#fbbf24` amber
- **Fonts:** JetBrains Mono (data/mono), Space Grotesk (headings)

### Page structure

One single page at `/`. Top to bottom:

1. **Header** — Site name + "AYN Thor Ship Date Estimator" subtitle
2. **Calculator card** — Model selector grid, order number input, result display (the existing component)
3. **Subscribe section** — Below the calculator result, a collapsible "Get notified" form: email input, model + order pre-filled, submit button. Shows inline success/error.
4. **Footer** — "Data scraped from ayntec.com" attribution link, "Last updated: {date}" from most recent scrape

If URL has `?verified=1`, show a toast: "Email verified! You'll be notified when your estimate changes."

### Components

```
Pages/
  Home.jsx              — The single page

Components/
  ModelSelector.jsx     — Grid of model variant buttons with color configs
  OrderInput.jsx        — 4-digit input with "xx" suffix
  ResultDisplay.jsx     — Shipped/Estimated/Extrapolated result card
  SubscribeForm.jsx     — Email notification signup with honeypot + timestamp
  Toast.jsx             — Success/error notifications
```

### Data flow

- On page load, Inertia passes all model variants (with `color_config`) and all shipping batches as props.
- Estimation runs entirely client-side in React (instant, no API calls).
- Subscribe form POSTs to `/api/subscribe`.
- The `/api/data` endpoint exists for potential future use (external consumers, refresh without page reload).

---

## File Structure

```
app/
  Console/Commands/
    Scrape.php               — `scrape` command
    Notify.php               — `notify` command
    SeedData.php             — `seed:data` command
  Http/
    Controllers/
      HomeController.php     — Inertia render with variants + batches
      SubscribeController.php— store(), verify(), unsubscribe()
      ApiController.php      — data() endpoint
    Requests/
      SubscribeRequest.php   — Validation + bot detection
  Models/
    ModelVariant.php
    ShippingBatch.php
    Subscriber.php
    ScrapeLog.php
  Mail/
    VerifySubscription.php
    EstimateChanged.php
    ScrapeAlert.php
  Services/
    ScraperService.php       — Fetch + parse + upsert + log
    EstimationService.php    — Interpolation/extrapolation logic

resources/
  js/
    app.jsx
    Pages/
      Home.jsx
    Components/
      ModelSelector.jsx
      OrderInput.jsx
      ResultDisplay.jsx
      SubscribeForm.jsx
      Toast.jsx
  views/
    app.blade.php            — Inertia root
    unsubscribed.blade.php   — Simple "you're unsubscribed" page
    mail/
      verify.blade.php
      estimate-changed.blade.php
      scrape-alert.blade.php

database/
  database.sqlite
  migrations/
    create_model_variants_table.php
    create_shipping_batches_table.php
    create_subscribers_table.php
    create_scrape_logs_table.php

routes/
  web.php    — GET /, GET /verify/{token}, GET /unsubscribe/{token}
  api.php    — GET /api/data, POST /api/subscribe
```

---

## Seed Data

The `seed:data` command creates the following:

### Model Variants

| display_order | name              | slug               | color_config |
|---------------|-------------------|--------------------|-------------|
| 1             | Rainbow Max       | rainbow-max        | `{"border":"#e8567d","accent":"#e8567d","tagBg":"#fff0f3"}` |
| 2             | Rainbow Pro       | rainbow-pro        | `{"border":"#d4943a","accent":"#d4943a","tagBg":"#fff8ed"}` |
| 3             | Black Max         | black-max          | `{"border":"#111","accent":"#000","tagBg":"#e8e8e8"}` |
| 4             | Black Pro         | black-pro          | `{"border":"#333","accent":"#1a1a1a","tagBg":"#f0f0f0"}` |
| 5             | Black Base        | black-base         | `{"border":"#555","accent":"#444","tagBg":"#f5f5f5"}` |
| 6             | Black Lite        | black-lite         | `{"border":"#777","accent":"#666","tagBg":"#f8f8f8"}` |
| 7             | White Max         | white-max          | `{"border":"#9a9ab0","accent":"#55557a","tagBg":"#e8e8f5"}` |
| 8             | White Pro         | white-pro          | `{"border":"#c0c0d0","accent":"#6a6a8a","tagBg":"#eeeef8"}` |
| 9             | Clear Purple Max  | clear-purple-max   | `{"border":"#9b59b6","accent":"#8e44ad","tagBg":"#f5eef8"}` |
| 10            | Clear Purple Pro  | clear-purple-pro   | `{"border":"#b07cc5","accent":"#a368b8","tagBg":"#f8f2fb"}` |

### Shipping Batches

Seed every row from the known data:

```
2026-01-15: rainbow-max 1062–1114, rainbow-pro 1050–1109
2026-01-19: rainbow-max 1114–1175
2026-01-20: rainbow-pro 1109–1147, black-pro 1067–1094, black-max 1067–1135
2026-01-21: white-pro 1072–1097, black-pro 1094–1139, black-base 1058–1088, black-max 1067–1147
2026-01-22: white-max 1064–1160, clear-purple-max 1058–1090, clear-purple-pro 1074–1117
2026-01-23: rainbow-max 1175–1210, clear-purple-max 1090–1102
2026-01-24: rainbow-pro 1147–1186, black-max 1148–1190, black-pro 1139–1195
2026-01-25: rainbow-max 1210–1258
2026-01-26: rainbow-pro 1186–1219, clear-purple-max 1102–1139, white-pro 1097–1279
2026-01-27: rainbow-max 1258–1286, clear-purple-max 1139–1153, clear-purple-pro 1117–1156, black-max 1190–1267
2026-01-28: rainbow-max 1286–1308
2026-01-29: rainbow-pro 1219–1291, black-max 1267–1300, black-pro 1195–1232
2026-01-30: rainbow-pro 1291–1295, rainbow-max 1300–1319, black-pro 1232–1285, black-base 1088–1269
2026-02-01: black-pro 1285–1311, black-max 1300–1352, black-base 1269–1344, clear-purple-max 1153–1172, clear-purple-pro 1156–1317
2026-02-02: rainbow-pro 1295–1364, white-pro 1279–1376
2026-02-03: rainbow-pro 1364–1386, black-pro 1311–1404, black-base 1344–1420
2026-02-04: rainbow-pro 1386–1395, black-lite 1254–1492, black-base 1420–1500
2026-02-05: rainbow-pro 1395–1438, white-pro 1376–1465, rainbow-max 1319–1343
2026-02-09: white-max 1160–1335, rainbow-max 1343–1417, black-max 1352–1402
2026-02-12: white-max 1335–1438, rainbow-max 1417–1426, black-max 1402–1437, clear-purple-max 1172–1439, rainbow-pro 1438–1440, white-pro 1376–1465, black-pro 1311–1437, black-base 1344–1510, clear-purple-pro 1317–1440
```

---

## Ploi.io Deployment

1. **Server:** PHP 8.3 server. Enable Supervisor for queue worker.
2. **Site:** Add site, standard Laravel.
3. **Deploy script** (append to Ploi defaults):
   ```bash
   npm install
   npm run build
   touch database/database.sqlite
   php artisan migrate --force
   php artisan queue:restart
   ```
4. **Browsershot dependencies** (run once on server):
   ```bash
   npx puppeteer install
   sudo apt-get install -y libgbm1 libnss3 libatk-bridge2.0-0 libdrm2 libxcomposite1 libxdamage1 libxrandr2 libgbm-dev libpango-1.0-0 libcairo2 libasound2
   ```
5. **Scheduler:** Enable in Ploi site settings.
6. **Queue worker:** Add daemon: `php artisan queue:work --sleep=3 --tries=3 --max-time=3600`.
7. **Environment** (`.env`):
   ```
   APP_URL=https://yourdomain.com
   DB_CONNECTION=sqlite
   DB_DATABASE=/full/path/to/database/database.sqlite
   QUEUE_CONNECTION=database
   ADMIN_EMAIL=your@email.com
   MAIL_MAILER=smtp
   MAIL_HOST=...
   MAIL_PORT=...
   MAIL_USERNAME=...
   MAIL_PASSWORD=...
   MAIL_FROM_ADDRESS=noreply@yourdomain.com
   MAIL_FROM_NAME="AYN Thor Ship Estimator"
   ```

---

## Build Order

### Phase 1 — Scaffold
1. `laravel new ayn-ship-estimator` with Inertia + React starter
2. Install Tailwind, configure dark theme, add Google Fonts (JetBrains Mono, Space Grotesk)
3. Create all 4 migrations, run them
4. Create all 4 Eloquent models with relationships
5. Build `seed:data` command with all hardcoded data, run it

### Phase 2 — Calculator frontend
6. `HomeController@index` — query variants + batches, pass to Inertia
7. Port the existing React calculator into `Pages/Home.jsx` — split into ModelSelector, OrderInput, ResultDisplay components
8. Build `/api/data` endpoint (JSON dump of variants + batches)
9. Wire up client-side estimation logic (already written in the React prototype)
10. Test all model variants with known order numbers against expected dates

### Phase 3 — Scraper
11. Install `spatie/browsershot`, verify Puppeteer works on server
12. Build `ScraperService` — fetch, parse, upsert, log
13. Build `scrape` artisan command
14. Test against live AYN page, iterate on parser until clean
15. Build `ScrapeAlert` mailable, wire up failure detection
16. Register scrape in scheduler (twice daily)

### Phase 4 — Notifications
17. Build `POST /api/subscribe` with `SubscribeRequest` (validation + bot detection)
18. Build `SubscribeForm.jsx`, wire into Home page below the result
19. Build `VerifySubscription` mailable + `/verify/{token}` route
20. Build `EstimationService` (PHP port — must match JS logic)
21. Build `notify` command — recalculate, compare, queue emails
22. Build `EstimateChanged` mailable
23. Build `/unsubscribe/{token}` route + simple blade page
24. Register notify in scheduler (twice daily, after scrapes)

### Phase 5 — Polish & deploy
25. Add Toast component for `?verified=1` feedback
26. Add "Last updated" footer from most recent successful `scrape_logs` entry
27. Add meta tags (title, description, og:image)
28. Error handling: 404, 500, rate limit 429 pages
29. Test full flow: visit → estimate → subscribe → verify email → scrape new data → receive notification → unsubscribe
30. Deploy via Ploi
