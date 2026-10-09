# FARM — Farm Management System

A production-oriented dairy farm management system built with Laravel, Blade, Tailwind CSS and DaisyUI.
The application models real farm business processes: animals and genealogy, feed procurement and
traceable inventory, health and breeding events, milk production, sales with receivables, and
cash-based finance.

---

## Table of contents

1. [Overview](#overview)
2. [Requirements](#requirements)
3. [Installation](#installation)
4. [Environment configuration](#environment-configuration)
5. [Database setup](#database-setup)
6. [Running locally](#running-locally)
7. [Testing](#testing)
8. [Demo accounts](#demo-accounts)
9. [Modules](#modules)
10. [Business rules](#business-rules)
11. [Architecture](#architecture)
12. [User interface](#user-interface)
13. [Deployment to cPanel](#deployment-to-cpanel)
14. [Troubleshooting](#troubleshooting)

---

## Overview

FARM answers the questions a dairy farm manager actually asks:

- How many animals do we have, and what is their status?
- Which animals are active, sold, deceased or transferred?
- How much milk are we producing, and is it rising or falling?
- How much feed is left, how fast are we consuming it, what needs reordering?
- What did we order, what actually arrived, and what is still outstanding?
- What do customers owe us? Which invoices are overdue?
- What do we owe suppliers?
- How much cash moved, and where did it go?

### Design principles

- **Business logic lives in services and models**, never in Blade templates.
- **Inventory is derived from stock movements.** No editable "stock = 5000" field exists.
- **History is preserved.** Sold and deceased animals are never deleted; their records stay queryable.
- **Master data and transaction data are separated.** Stable reference data versus repeatable events.
- **The system reports facts, it does not invent diagnoses.** A production drop is flagged for human
  investigation; the system never claims an animal is sick.
- **Revenue, receivables and cash are separate concepts** and are shown separately.

---

## Requirements

| Requirement | Version |
|---|---|
| PHP | 8.3 or newer (with `pdo_mysql`) |
| Composer | 2.5 or newer |
| Node.js | 20 or newer |
| MySQL | 8.0+ or MariaDB 10.6+ |
| Web server | Apache with `mod_rewrite`, or Nginx |

PHP extensions required: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`.

Check your PHP version and extensions:

```bash
php -v
php -m | grep -E "pdo_mysql|mbstring|openssl"
```

---

## Installation

### Quick start (development)

```bash
git clone <repository-url> farm
cd farm

composer install
cp .env.example .env
php artisan key:generate
```

Configure the database in `.env` (see next section), then:

```bash
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Open <http://localhost:8000> and sign in with a demo account.

### The `composer run setup` shortcut

```bash
composer run setup
```

This runs `composer install`, copies `.env`, generates a key, migrates, installs npm packages and
builds assets. Add `--seed` afterwards to load the demo dataset:

```bash
php artisan migrate:fresh --seed
```

---

## Environment configuration

Relevant `.env` keys:

```dotenv
APP_NAME=FARM
APP_ENV=production
APP_DEBUG=false
APP_URL=https://farm.example.com
APP_TIMEZONE=Asia/Jakarta

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=farm
DB_USERNAME=farm_user
DB_PASSWORD=change-me
# DB_SOCKET=/opt/lampp/var/mysql/mysql.sock

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

MAIL_MAILER=log
```

**Important**

- Never commit `.env`. It is already listed in `.gitignore`.
- Set `APP_DEBUG=false` and `APP_ENV=production` on any public server.
- Choose an `APP_URL` that matches the real domain, otherwise generated links in emails and
  pagination will point at the wrong host.
- `SESSION_SECURE_COOKIE=true` is recommended when serving over HTTPS.

---

## Database setup

### MySQL

```sql
CREATE DATABASE farm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'farm_user'@'localhost' IDENTIFIED BY 'change-me';
GRANT ALL PRIVILEGES ON farm.* TO 'farm_user'@'localhost';
FLUSH PRIVILEGES;
```

### Migrations

```bash
php artisan migrate          # create the schema
php artisan migrate:fresh --seed   # rebuild schema and load demo data
php artisan migrate:status         # inspect applied migrations
```

### Seeders

```bash
php artisan db:seed
```

The seeders run in dependency order and deliberately go through the real application services, so
the demo data is produced by the same code paths as live input:

| Seeder | Contents |
|---|---|
| `SettingSeeder` | System settings (payment terms, decline thresholds, currency) |
| `FarmDataSeeder` | 1 farm, 6 barns, 5 breeds, 6 users, 47 animals with genealogy and exits |
| `InventoryDataSeeder` | 7 feed items, 3 suppliers, opening balances, a partially received purchase order, 30 days of consumption |
| `HerdHistorySeeder` | Health records, vaccinations, treatments, breeding, pregnancy checks, births |
| `MilkProductionSeeder` | 46 days of milk production per animal plus one barn-level entry |
| `SalesFinanceSeeder` | 4 products, 5 customers, sales orders, invoices, payments, expenses, other income, cash movements |

The demo dataset demonstrates these scenarios end to end:

1. A purchase order for **5,000 kg** is only **4,000 kg** received, leaving **1,000 kg outstanding**.
2. Feed consumption of **800 kg** is drawn from the delivered batch and leaves a traceable balance.
3. A customer invoice on **Net 30** terms raises revenue and receivables while cash stays unchanged.
4. A payment reduces receivables and increases cash.
5. Invoices past their due date are detected and reported as overdue.
6. One animal's production drops in the recent window; the system flags the change without
   diagnosing a cause.
7. A sold animal and a deceased animal remain in the database with their history intact.

---

## Running locally

```bash
# terminal 1
php artisan serve

# terminal 2 (only while developing styles)
npm run dev
```

For a production-style local run:

```bash
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan serve
```

After changing `.env`, clear the caches:

```bash
php artisan optimize:clear
```

---

## Testing

The project uses PHPUnit 12.

```bash
php artisan test                      # run everything
php artisan test --compact            # summarised output
vendor/bin/phpunit                    # direct runner
vendor/bin/phpunit --filter=InventoryTest
```

### Test coverage

| Test file | What it proves |
|---|---|
| `tests/Feature/InventoryTest.php` | Purchase orders do not increase stock; draft receipts do not increase stock; posted receipts increase stock and create batches; partial receipts preserve the outstanding quantity; consumption decreases stock and is traceable to a batch; over-consumption is blocked without an override and allowed with one; adjustments require a reason; low-stock detection; FIFO allocation; allocations do not double-deduct; reversal returns stock |
| `tests/Feature/FinanceTest.php` | Invoicing raises receivables without touching cash; payments reduce receivables and increase cash; partial payments; overdue detection; expired invoices report only the outstanding amount; expenses reduce cash; revenue and cash are reported separately |
| `tests/Feature/AnimalManagementTest.php` | Unique animal codes; sex and status coherence; sold animals are never deleted and keep their history; genealogy relationships; animals cannot be their own parent; barns must belong to the same farm |
| `tests/Feature/BreedingAndProductionTest.php` | Mating alone does not imply pregnancy; heat observation changes nothing; negative pregnancy checks return the animal to open; due dates derive from the mating date; births link genealogy and reactivate the dam; production totals; declines are reported as numbers without diagnosis; birth validation rules |
| `tests/Feature/AuthorizationTest.php` | Role-based module and ability access |
| `tests/Feature/SeederSmokeTest.php` | The demo dataset seeds without error and preserves every business invariant |
| `tests/Feature/PageRenderTest.php` | Every GET page renders successfully for every role |

---

## Demo accounts

All demo accounts use the password **`password`**.

| Role | Email | Can do |
|---|---|---|
| Owner | `owner@farm.test` | Everything: operations, finance, reports, settings, user management |
| Farm Manager | `manager@farm.test` | Operations, inventory, purchasing, animal management, sales, approvals |
| Farm Worker | `worker@farm.test` | Record feed consumption and milk production |
| Veterinarian | `vet@farm.test` | Health records, treatments, vaccinations, breeding and pregnancy checks |
| Finance / Admin | `finance@farm.test` | Customers, invoices, payments, expenses, income, cash flow, financial reports |

Authorization is enforced in three layers: route module middleware (`module:feed`), route ability
middleware (`ability:feed.consume`) and `FormRequest::authorize()`. Navigation items are filtered by
role, so users never see a page they cannot open.

---

## Modules

### Dashboard
"What requires my attention today": low stock, overdue invoices, outstanding purchase orders,
overdue vaccinations, open health cases, imminent births, negative stock, production declines,
supplier liabilities. Plus herd counts, production today/month with a trend comparison, financial
position (revenue, expense, profit estimate, receivable, payable, cash), top producers, recent
expenses and recent activity.

### Farm
- **Farms** — the operating unit, address, area, manager.
- **Barns** — typed housing (dairy, calf, dry, quarantine, breeding) with capacity and occupancy.
- **Breeds** — breed reference with productivity parameters.
- **Animals** — unique code, breed, sex, birth date, barn, ownership and genealogy.

### Feed & Inventory
- **Feed items** — category, unit, price, reorder point and quantity.
- **Suppliers** — contacts, payment terms, ledger of purchase orders and receipts.
- **Purchase orders** — a commitment only. Includes approval and cancellation.
- **Goods receipts** — the only document that increases stock. Supports partial receipt.
- **Inventory** — current stock per item derived from movements, low-stock warnings, days of cover.
- **Batches** — traceability from supplier to batch to consumption.
- **Stock movements** — the audit ledger: receipt, consumption, wastage, adjustment, transfer, return.
- **Feed consumption** — barn-level and individual-animal records, with FIFO batch allocation.

### Animal Care
- **Health records** — observations, checkups, diagnoses, severity, status, follow-up, cost.
- **Vaccinations** — vaccine, batch, dose, route, next due date, overdue alerts.
- **Treatments** — medication, dosage, route, frequency, withdrawal period and milk-withholding warnings.

### Breeding
- **Breeding records** — heat observation, insemination, natural mating, synchronisation.
- **Pregnancy checks** — method, result, gestation age, expected due date, fetus count.
- **Birth records** — dam + sire + calf, birth type, weight, colostrum, and optional calf registration
  which creates a real animal record with genealogy attached.

### Production
- **Milk production** — per animal or per barn group, with sessions and quality readings.
- **Production reports** — daily/weekly/monthly series, per-animal and per-barn comparison, trend
  against the previous period, and animals whose yield dropped.

### Sales
- **Customers** — credit limit, payment terms, outstanding balance.
- **Sales orders** — line items, confirmation, conversion to an invoice.
- **Invoices** — invoice number, issue date, due date, totals, derived balance, overdue detection.
- **Payments** — reduces receivables, increases cash, supports partial settlement.

### Finance
- **Expenses** — eight categories, vendor, reference, payment method.
- **Other income** — manure, livestock disposal, subsidy; sales revenue is invoiced instead.
- **Cash flow** — the cash ledger with a running balance.
- **Supplier payments** — reduces payables created by posted goods receipts.

### Reports
Animal, feed, production and financial reports — all filtered by farm and period.

### Settings and audit
System settings (default payment terms, decline thresholds, override policy) and user management,
plus an activity log recording who did what, when, why and against which document.

---

## Business rules

### Inventory

1. A **purchase order never changes stock**. It records an intention to buy.
2. Only a **confirmed goods receipt increases stock**, and it automatically creates an
   `InventoryBatch` and an inbound `StockMovement`.
3. A **draft goods receipt does not change stock** until it is confirmed.
4. **Partial receipts are supported.** Ordered, received and outstanding quantities are preserved and
   the purchase order status becomes "Diterima Sebagian".
5. **Stock is always derived** from the sum of signed stock movements.
6. **Consumption cannot silently exceed available stock.** When it would, the request is rejected
   with a clear message showing requested, available and shortfall quantities.
7. An **explicit override** is possible for owner and manager roles. It requires a written reason,
   records who approved it, posts an explicit adjustment movement so the ledger still explains the
   difference, and writes an audit entry.
8. **Adjustments require a reason** and appear in the movement ledger.
9. **FIFO batch allocation** is used for consumption; consumed batches are marked depleted when
   their remaining quantity reaches zero.
10. Inbound corrections create an implicit batch so traceability and FIFO allocation stay intact.

### Feed consumption

11. A consumption document belongs to a **barn** (group record) and optionally to individual animals.
12. **Individual allocations never deduct stock a second time.** Only the consumption line moves
    stock; allocations are operational detail.
13. A consumption can be **reversed with a reason**, which posts compensating inbound movements
    instead of deleting history.

### Animals

14. Animal codes are unique.
15. **Sold, deceased and transferred animals are never deleted.** Exiting an animal requires a date,
    a reason, and a value for a sale, and is written to the audit log.
16. **Genalogy is bidirectional**: an animal knows its dam and sire, and both know their offspring.
17. An animal cannot be its own parent; a dam must be female and a sire must be male.
18. A barn must belong to the same farm as the animal.
19. Males cannot be lactating, and breeding status is "not applicable" for males.

### Status modelling

20. **Existence status** (`active`, `sold`, `deceased`, `transferred`) is separate from
    **production status** (`calf`, `heifer`, `lactating`, `dry`, `fattening`, `bull`) and from
    **breeding status** (`open`, `bred`, `pregnant`, `confirmed_pregnant`, `not_applicable`).
    An animal can be lactating, pregnant and under treatment at the same time.
21. **Health status is derived** from open health records, never inferred from production figures.

### Health

22. Health records are historical and are never rewritten by later events.
23. A record of type "diagnosis" requires a diagnosis.
24. A record marked resolved requires a resolution date.
25. **The system does not infer illness from production changes.** It reports the number and asks
    humans to investigate.
26. Treatments with a withdrawal period flag when milk may be consumed again.

### Breeding

27. **Pregnancy is never assumed from mating.** A mating record moves the animal to "bred, awaiting
    check" only.
28. Only a pregnancy check with a positive result sets a pregnant status; a negative or unknown
    result returns the animal to the open cycle.
29. Expected due dates derive from the mating date using the standard 283-day gestation when not
    supplied.
30. A birth reactivates the dam (lactating) and resets her breeding status to open.
31. A stillborn calf cannot be registered as an animal.
32. A birth record requires a female dam; the sire must be male and different from the dam.

### Production

33. A production record belongs to **one animal or one barn, never both**.
34. Only female animals in "lactating" status can receive individual production records.
35. Duplicate entries for the same subject, date and session are rejected.
36. A **decline is reported with a percentage against the previous equal-length window** and nothing
    more.

### Sales, receivables and cash

37. **Recognising revenue and receiving cash are separate.** An invoice raises revenue and
    receivables; cash only moves when a payment is recorded.
38. **Invoice balance is derived** (`total - paid_amount`), never stored, so it cannot disagree with
    the payment history.
39. **"Overdue" is derived** from the due date and the outstanding balance rather than stored as a
    status that could drift.
40. Invoice status is recalculated from the sum of payments: unpaid, partially paid, paid, cancelled.
41. An invoice with payments cannot be cancelled.
42. Customer sales revenue is **not** recorded again in "other income", which would double-count it.

### Expenses, payables and cash flow

43. An expense record also creates a cash outflow; an other-income record also creates a cash inflow.
44. **Payables arise from posted goods receipts** and are reduced by supplier payments.
45. Cash transactions for customer receipts, supplier payments and expenses are created
    automatically by their own modules; the manual cash form is limited to other flows such as
    owner's drawings, to avoid duplicate postings.
46. **Profit estimate and cash flow are reported separately.** Profit is revenue plus other income
    minus expenses; net cash is the difference of actual cash movements.

### Traceability

47. Important transactions record the acting user, a timestamp, a document reference and a reason.
48. Inventory can always be explained by pointing at the movement that caused it.

---

## Architecture

```
app/
  Enums/                 Status and domain value objects with labels and behaviour
  Http/
    Controllers/         Thin controllers; Form Requests own validation
    Requests/            Validation, authorisation and cross-field rules
  Models/                Relationships, casts, query scopes, derived accessors
  Services/
    AuditLogger.php      Lightweight who/what/when/why trail
    InventoryService.php Goods receipts, consumption, adjustments, stock derivation
    FeedConsumptionService.php  Consumption records, allocations, reversal
    BreedingService.php  Breeding, pregnancy and birth side effects
    MilkProductionService.php    Production records and status changes
    FinanceService.php   Invoices, payments, expenses, cash, receivables and payables
    AttentionService.php  Dashboard alert list
    ProductionAnalyticsService.php  Totals, trends and decline detection
  Support/
    ModuleGuard.php      Route middleware for module access
    AbilityGuard.php     Route middleware for a single ability
    Navigation.php       Single source of truth for sidebar and route guards
routes/
  web.php                Auth and dashboard
  web/farm.php           Farm, barns, breeds, animals
  web/feed.php           Feed, procurement, inventory, consumption
  web/health.php         Health, vaccinations, treatments
  web/breeding.php       Breeding, pregnancy checks, births
  web/production.php     Milk production
  web/sales.php          Customers, orders, invoices, payments
  web/finance.php        Expenses, income, cash flow, supplier payments
  web/reports.php        Reports, activity log, settings
resources/
  views/                 Blade views, grouped per module
    components/          Reusable UI: panel, stat, badge, money, form fields, alerts
    layouts/             App shell with sidebar, and a guest layout for login
database/
  migrations/            One migration per domain group
  factories/             25 factories
  seeders/               Demo dataset built through the real services
```

### Design decisions worth knowing

- **Role-based access without extra packages.** Roles live in an enum; abilities are listed per role.
  No permission tables, no package dependency.
- **Derived stock.** `StockMovement::scopeSigned()` builds the signed sum in SQL so stock can be
  aggregated in the database rather than in PHP.
- **Date handling.** `date`-casted columns are compared with `whereDate()` everywhere so behaviour is
  identical on MySQL and SQLite.
- **Presentation values are computed in controllers**, keeping Blade templates free of business logic
  and escaping problems.
- **Demo data is created through the services**, so the seeded database could only exist if the
  business rules hold.

---

## User interface

The interface is bilingual and DaisyUI-first. There is no separate frontend build step
beyond Vite, and no JavaScript framework.

### Theme

`resources/css/app.css` defines a single custom DaisyUI theme named `farm`
(`themes: false` disables the 35 built-in themes). Built-in themes were evaluated and
rejected: `corporate` is blue, `emerald` is a pale green on cool greys, `autumn` reads as
an error state, and `business` is a dark base.

The theme is deliberately restrained and stays light-only:

| Token | Value | Intent |
| --- | --- | --- |
| `--color-primary` | `oklch(45% 0.075 145)` | deep olive green |
| `--color-secondary` | `oklch(48% 0.055 55)` | muted brown |
| `--color-base-100` | `oklch(99% 0.006 85)` | warm cream surface |
| `--radius-box` | `0.375rem` | quiet corners |
| `--depth` / `--noise` | `0` | flat surfaces, no gradients |

Tailwind is used for layout only (grid, flex, spacing, responsive). Use DaisyUI
components for anything that looks like a control.

### Component conventions

Reuse these instead of hand-rolling markup. The names are unchanged, so the ~800
existing call sites keep working while the internals sit on DaisyUI primitives.

| Component | Built on | Notes |
| --- | --- | --- |
| `<x-panel>` | `card` | The single bordered container for the app |
| `<x-stat>` | `card` + `stat-*` | Metric tile. DaisyUI's `stats` wrapper is **not** used: it forces `overflow-x: auto` and `white-space: nowrap` on every child, which breaks a responsive grid of separate tiles |
| `<x-badge>` | `badge` | `class` takes a real modifier (`badge-soft badge-success`, `badge-ghost`) |
| `<x-alert-row>` | `alert alert-soft` | Inline attention row |
| `<x-empty-state>` | `card card-dash` | Empty placeholder |
| `<x-page-header>` | `card-title` | Page title, back link, actions |
| `<x-section-title>` | `menu-title` styling | Small uppercase section heading |
| `<x-icon name="...">` | — | The only icon source. One stroke width, no icon dependency |
| `<x-form.*>` | `input` / `select` / `textarea` / `checkbox` / `join` | Labels, hints and errors with `aria-invalid` + `aria-describedby` |

Rules that the codebase now enforces:

- Status colours come from `Enum::badgeClass()` and are **DaisyUI modifiers only**. Do not
  reintroduce composites such as `badge-error/15 text-error border-error/25`; use
  `badge-soft badge-error`.
- Tables use `table table-sm table-zebra`. There is no `.table-compact`.
- Buttons are written variant-then-size: `btn btn-primary btn-sm`, `btn btn-ghost btn-xs`.
- Meter bars use `<progress class="progress ...">`.
- Never add `input-bordered` / `select-bordered` / `textarea-bordered`. DaisyUI 5 removed
  them, so they emit nothing.

### Pagination

Laravel's default paginator ships a Tailwind view full of `gray-*`, `blue-*` and `dark:*`
utilities, which does not match this theme. `AppServiceProvider` points every paginator at
`resources/views/vendor/pagination/farm.blade.php`.

### Bilingual interface

English is the **source language**: an untranslated English label renders as itself, so
`lang/en.json` does not exist. `lang/id.json` maps English keys to Indonesian, and
`config('app.locale')` is `id`, so Indonesian is the shipped default.

- To add a label, write the English text and wrap it in `__()` (`__('Milk today')`), then add
  the Indonesian entry to `lang/id.json`.
- Enums resolve their labels through `__()`, so every status badge, dropdown and table cell
  is translated from one place.
- The active language is stored in the session by `PUT /locale` and applied by
  `App\Http\Middleware\ApplyLocale`, which runs inside the web group so the session is
  available. Switch it from the user menu.
- `App\Support\Locale` owns the supported list and normalisation.

Currently translated: navigation, layouts, shared components, all enum labels, the dashboard
and pagination. Page-level prose in individual module views is still Indonesian-only and can
be migrated with the same `__()` pattern when needed.

---

## Deployment to cPanel

### 1. Prepare on the server

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 2. Upload

Upload the project to a directory outside the public root, for example `/home/youruser/farm`.
Only `public/` should be exposed to the web.

```
/home/youruser/farm/app
/home/youruser/farm/config
/home/youruser/farm/storage
/home/youruser/farm/public      <-- document root
/home/youruser/public_html     <-- point at farm/public, or use .htaccess proxy
```

Common approaches:

- **Subdomain** (recommended): set the document root to `/home/youruser/farm/public`.
- **Subfolder**: keep `public_html` as the root and add an `.htaccess` in `public/` that forwards
  everything to the real `public/` directory.

### 3. `.env` on the server

```dotenv
APP_NAME=FARM
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
APP_KEY=base64:...        # from php artisan key:generate --show

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=youruser_farm
DB_USERNAME=youruser_farm
DB_PASSWORD=strong-password

SESSION_DRIVER=database
CACHE_STORE=file
QUEUE_CONNECTION=sync
MAIL_MAILER=smtp
```

Prefer `CACHE_STORE=file` and `QUEUE_CONNECTION=sync` on shared hosting — no extra daemons.

### 4. Create the database in cPanel

cPanel → **MySQL Databases**: create the database and a user with full privileges.

### 5. File permissions

```bash
chmod -R 775 storage bootstrap/cache
chmod 644 .env
```

### 6. Run the deployment commands

```bash
cd /home/youruser/farm
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 7. Load demo data (optional)

```bash
php artisan db:seed --force
```

Demo accounts use the password `password`. **Change or delete them before going live.**

### 8. Apache / PHP settings

- PHP version: 8.3 or newer.
- `extension` enabled: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`.
- If `.htaccess` rewrites are ignored, enable `AllowOverride All` for the directory, or configure
  the rewrite in `public/.htaccess` manually.
- The `public` directory must be the document root. Never expose the project root.

### 9. HTTPS

Install a free SSL certificate in cPanel and force HTTPS. Also set:

```dotenv
SESSION_SECURE_COOKIE=true
```

### 10. Scheduled maintenance

Add a cron entry in cPanel for:

```
* * * * * cd /home/youruser/farm && php artisan schedule:run >> /dev/null 2>&1
```

### Deployment checklist

- [ ] `APP_DEBUG=false`, `APP_ENV=production`
- [ ] `APP_URL` matches the real domain
- [ ] `APP_KEY` regenerated on the server
- [ ] `.env` is not readable through the web and not committed to Git
- [ ] Document root points at `public/`
- [ ] `storage` and `bootstrap/cache` are writable
- [ ] Caches built with `config:cache`, `route:cache`, `view:cache`
- [ ] Demo accounts changed or removed
- [ ] HTTPS enabled

---

## Troubleshooting

**"No such file or directory" for the MySQL socket**
`DB_SOCKET` points at a path that does not exist on this machine. Remove the line to use the default
socket, or set it to the correct one (`/var/run/mysqld/mysqld.sock` on most distributions).

**"Access denied for user"**
Check `DB_USERNAME`, `DB_PASSWORD` and that the user has privileges on `DB_DATABASE`.

**Styling looks unstyled**
Assets are not built. Run `npm install && npm run build`. If you see "Unable to locate file in Vite
manifest", run `php artisan optimize:clear`.

**Sidebar shows fewer items than expected**
Navigation is role-driven. Sign in with a different account, or check `Route::has()` filtering in
`App\Support\Navigation`.

**Stock shows zero after receiving goods**
Confirm the receipt status is "Dikonfirmasi". Draft receipts intentionally do not change stock.

**"Jumlah melebihi sisa pesanan" when receiving goods**
The receipt would exceed the ordered quantity. Create a separate purchase order for the difference.

**Running tests**
Tests use SQLite in memory, so they never touch your MySQL data:
`php artisan test`.