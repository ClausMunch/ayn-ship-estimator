# AYN Thor Ship Date Estimator

A single-purpose shipping estimate tool for AYN Thor handhelds. Pick your model variant, enter the first 4 digits of your order number, and get an interpolated or extrapolated ship date based on real shipping data.

## Features

- **Instant estimates** — client-side interpolation/extrapolation, no API calls needed
- **10 model variants** — Rainbow Max/Pro, Black Max/Pro/Base/Lite, White Max/Pro, Clear Purple Max/Pro
- **Email notifications** — subscribe to get notified when your estimated ship date changes
- **Automated scraping** — twice-daily scrape of the AYN shipping dashboard with admin alerts on failure
- **Bot protection** — honeypot field, signed timestamp, and rate limiting (no third-party CAPTCHA)

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 12, PHP 8.3+ |
| Database | SQLite |
| Frontend | Inertia.js + React |
| Styling | Tailwind CSS v4 |
| Scraping | spatie/browsershot (Puppeteer) |
| Queue | SQLite-backed (`database` driver) |
| Mail | Laravel Mail via SMTP |

## Local Development

### Requirements

- PHP 8.3+
- Composer
- Node.js 20+ (or Bun)
- Puppeteer system dependencies (for scraping)

### Setup

```bash
# Install dependencies
composer install
npm install      # or: bun install

# Environment
cp .env.example .env
php artisan key:generate

# Database
touch database/database.sqlite
php artisan migrate
php artisan seed:data

# Build frontend
npm run build    # or: bun run build

# Serve
php artisan serve
```

The app will be available at `http://localhost:8000`.

### Development server with hot reload

```bash
npm run dev      # or: bun run dev
```

## Artisan Commands

| Command | Description |
|---------|-------------|
| `php artisan seed:data` | Seed model variants and historical shipping batches (idempotent) |
| `php artisan scrape` | Scrape the AYN shipping dashboard and update batch data |
| `php artisan notify` | Check for estimate changes and notify verified subscribers |

## Scheduler

The following jobs run automatically when the Laravel scheduler is active:

```
06:00 / 18:00  —  scrape    (fetch latest shipping data)
06:30 / 18:30  —  notify    (email subscribers if estimates changed)
```

Enable with a cron entry:

```
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

## Deployment (Ploi.io)

### Server requirements

- PHP 8.3+
- Node.js 20+
- Supervisor (for queue worker)

### System dependencies for Puppeteer

```bash
npx puppeteer install
sudo apt-get install -y \
  libgbm1 libnss3 libatk-bridge2.0-0 libdrm2 libxcomposite1 \
  libxdamage1 libxrandr2 libgbm-dev libpango-1.0-0 libcairo2 \
  libasound2t64
```

> **Note:** On Ubuntu 24.04+, use `libasound2t64` instead of `libasound2`.

### Deploy script

Append to Ploi defaults:

```bash
npm install
npm run build
npx puppeteer install
touch database/database.sqlite
php artisan migrate --force
php artisan queue:restart
```

### Queue worker

Add as a Ploi daemon:

```bash
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

### Environment variables

```env
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

## Project Structure

```
app/
  Console/Commands/
    Scrape.php                Scrape the AYN shipping dashboard
    Notify.php                Notify subscribers of estimate changes
    SeedData.php              Seed model variants + historical data
  Http/Controllers/
    HomeController.php        Inertia page with variants + batches
    SubscribeController.php   Subscribe, verify, unsubscribe
    ApiController.php         JSON data endpoint
  Mail/
    VerifySubscription.php    Email verification
    EstimateChanged.php       Estimate change notification
    ScrapeAlert.php           Admin alert on scrape failure
  Models/
    ModelVariant.php          10 AYN Thor variants
    ShippingBatch.php         Shipping date + order range data points
    Subscriber.php            Email notification subscribers
    ScrapeLog.php             Scrape attempt history
  Services/
    ScraperService.php        Fetch, parse, upsert shipping data
    EstimationService.php     Interpolation/extrapolation (PHP)

resources/js/
  Pages/Home.jsx              Single-page calculator
  Components/
    ModelSelector.jsx          Model variant button grid
    OrderInput.jsx             4-digit order number input
    ResultDisplay.jsx          Shipped/estimated/extrapolated result
    SubscribeForm.jsx          Email notification signup
    Toast.jsx                  Success/error notifications
  lib/estimation.js            Client-side estimation logic
```

## How Estimation Works

1. Shipping batches are collected as data points: `(date, cumulative_order_end)`
2. If your order prefix is at or below the first data point, your order **already shipped**
3. If it falls between two data points, the date is **interpolated** linearly
4. If it's beyond all known data, the date is **extrapolated** from the last two points

Both the JavaScript (client-side) and PHP (server-side for notifications) implementations produce identical results.
