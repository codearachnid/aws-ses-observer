# AWS SES Observer — Laravel Package Implementation Plan

## Overview

AWS SES Observer is a Laravel package that provides email observability for AWS SES. It ingests SNS webhook notifications and presents deliveries, bounces, complaints, opens, clicks, and other SES events in a self-hosted dashboard. Install it into any Laravel 13 app via Composer.

**Inspired by**: [Sessy](https://github.com/marckohlbrugge/sessy) (Rails) and 37signals' Fizzy — but designed from the ground up as a first-class Laravel package with its own identity, conventions, and architecture.

### Core Capabilities
- Receive and verify AWS SNS webhook notifications
- Ingest and deduplicate SES email events (10 event types)
- Dashboard with 30-day analytics, charts, and filtering
- Per-source retention policies with automatic cleanup
- CSV export of filtered events
- Optional HTTP Basic auth for the dashboard
- Works with both SQLite and PostgreSQL

---

## Package Identity

- **Repo**: `codearachnid/aws-ses-observer`
- **Namespace**: `codearachnid\AwsSesObserver`
- **Config key**: `aws-ses-observer`
- **Table prefix**: `ses_observer_`
- **Route prefix**: configurable (default `/ses-observer`)
- **View namespace**: `aws-ses-observer::`
- **Working directory**: `/Users/codearachnid/Sites/laravel/package/aws-ses-observer`
- **Skeleton**: Spatie `laravel-package-tools` (already configured)

---

## Phase 0: Package Scaffolding

### 0.1 Current State
The Spatie skeleton is configured. Existing files:
- `src/AwsSesObserverServiceProvider.php` — stub service provider
- `src/AwsSesObserver.php` — main class stub
- `src/Facades/AwsSesObserver.php` — facade stub
- `src/Commands/AwsSesObserverCommand.php` — stub command
- `config/aws-ses-observer.php` — stub config
- `tests/TestCase.php` — Orchestra Testbench base with factory guessing
- `tests/Pest.php` — Pest 4 binding
- `tests/ArchTest.php` — arch test (no dd/dump/ray)

### 0.2 Additional Dependencies

```bash
composer require livewire/livewire livewire/flux aws/aws-sdk-php league/csv
npm install chart.js local-time highlight.js
```

### 0.3 Service Provider

```php
namespace codearachnid\AwsSesObserver;

use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class AwsSesObserverServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('aws-ses-observer')
            ->hasConfigFile()
            ->discoversMigrations()
            ->hasViews()
            ->hasAssets()
            ->hasRoute('web')
            ->hasCommand(Commands\InstallCommand::class);
    }

    public function packageBooted(): void
    {
        Livewire::component('ses-observer.source-index', Livewire\SourceIndex::class);
        Livewire::component('ses-observer.source-show', Livewire\SourceShow::class);
        Livewire::component('ses-observer.source-form', Livewire\SourceForm::class);
        Livewire::component('ses-observer.event-list', Livewire\EventList::class);
        Livewire::component('ses-observer.message-detail', Livewire\MessageDetail::class);
        Livewire::component('ses-observer.setup-guide', Livewire\SetupGuide::class);

        $this->app['router']->aliasMiddleware(
            'ses-observer.auth', Http\Middleware\OptionalBasicAuth::class
        );
    }
}
```

### 0.4 Config

```php
// config/aws-ses-observer.php
return [
    'route_prefix' => env('SES_OBSERVER_ROUTE_PREFIX', 'ses-observer'),
    'middleware' => ['web', 'ses-observer.auth'],
    'table_prefix' => 'ses_observer_',
    'http_auth_username' => env('SES_OBSERVER_AUTH_USERNAME'),
    'http_auth_password' => env('SES_OBSERVER_AUTH_PASSWORD'),
    'disable_auth_warning' => env('SES_OBSERVER_DISABLE_AUTH_WARNING', false),
    'sns_signature_verification' => env('SES_OBSERVER_VERIFY_SNS', true),
];
```

---

## Phase 1: Foundation (DB, Models, Enums)

### 1.1 Dual-Database Support
The package inherits the host app's `DB_CONNECTION`. All migrations use `json` column types (not `jsonb`) for SQLite/PostgreSQL compatibility.

### 1.2 Migrations (4 files in `database/migrations/`)

**`create_ses_observer_sources_table.php`**
| Column | Type | Notes |
|--------|------|-------|
| id | bigIncrements | |
| name | string | required |
| token | string | unique, auto-generated UUID |
| color | string | default: "blue" |
| retention_days | integer | nullable |
| timestamps | | |

**`create_ses_observer_webhooks_table.php`**
| Column | Type | Notes |
|--------|------|-------|
| id | bigIncrements | |
| sns_message_id | string | unique |
| sns_type | string | SubscriptionConfirmation, Notification, etc. |
| sns_timestamp | timestamp | |
| raw_payload | json | full SNS message body |
| processed_at | timestamp | nullable, indexed |
| timestamps | | |

**`create_ses_observer_messages_table.php`**
| Column | Type | Notes |
|--------|------|-------|
| id | bigIncrements | |
| source_id | foreignId | cascadeOnDelete |
| ses_message_id | string | unique |
| subject | string | nullable |
| source_email | string | nullable, indexed |
| sent_at | timestamp | nullable, indexed |
| mail_metadata | json | nullable, full SES mail object |
| events_count | unsignedInteger | default: 0 (counter cache) |
| timestamps | | |

**`create_ses_observer_events_table.php`**
| Column | Type | Notes |
|--------|------|-------|
| id | bigIncrements | |
| message_id | foreignId | cascadeOnDelete |
| webhook_id | foreignId | nullable, nullOnDelete |
| event_type | string | Send, Delivery, Bounce, etc. |
| recipient_email | string | indexed, stored lowercase |
| event_at | timestamp | indexed |
| ses_message_id | string | indexed |
| event_data | json | nullable, event-specific data |
| raw_payload | json | nullable |
| bounce_type | string | nullable (Permanent, Transient, Undetermined) |
| timestamps | | |
| **unique index** | | `(ses_message_id, event_type, recipient_email, event_at)` — deduplication key |

### 1.3 Enums (in `src/Enums/`)

**`EventType.php`** — string-backed enum with 10 cases:
```
Send, Delivery, Bounce, Complaint, Reject, DeliveryDelay,
RenderingFailure, Subscription, Open, Click
```
Methods: `label()`, `badgeClasses()`, `filterChipClasses()`

**`BounceType.php`** — string-backed enum: `Permanent`, `Transient`, `Undetermined`
Methods: `label()` (e.g., "Hard Bounce", "Soft Bounce")

**`SourceColor.php`** — string-backed enum with 8 values: purple, blue, cyan, green, red, orange, yellow, gray
Methods: `cssClass()` (returns Tailwind bg-* class), `static nextAvailable()` (round-robin by least-used count)

### 1.4 Models (in `src/Models/`)

**`Source`**
- Relationships: `hasMany(Message)`, `hasManyThrough(Event, Message)`
- Traits: `HasRetentionPolicy`
- Boot: auto-generate UUID token on creating
- Cast: `color` → `SourceColor` enum
- Scopes: `alphabetically()`
- Validation: name required, token unique, color in enum, retention_days positive integer or null

**`Message`**
- Relationships: `belongsTo(Source)`, `hasMany(Event)`
- Route key: `ses_message_id` (via `getRouteKeyName()`)
- Methods: `destinationEmails()` (from mail_metadata), `tags(bool $includeSes = false)` (from mail_metadata)
- Static: `findOrCreateFromPayload(EventPayload $payload, Source $source)`

**`Event`**
- Relationships: `belongsTo(Message)`, `belongsTo(Webhook)` (optional)
- Cast: `event_type` → `EventType` enum
- Mutator: `recipient_email` → lowercase + trim
- Scopes: `search($term)` (LIKE on recipient_email + joined message.subject), `withEventTypes(array)`, `withBounceTypes(array)`, `betweenDates($from, $to)`, `filterByParams(array $params)`, `reverseChronologically()`
- Static: `filterCounts(array $params)` — returns counts grouped by event_type and bounce_type
- Method: `isPermanentBounce()`

**`Webhook`**
- Relationships: `hasMany(Event)`
- Methods: `markAsProcessed()`

**`Concerns/HasRetentionPolicy`** (trait)
- Scope: `withRetentionPolicy()` — `whereNotNull('retention_days')`
- Method: `deleteExpiredData(): int` — deletes messages + cascaded events older than `retention_days`, returns count

### 1.5 EventPayload DTO (`src/DataTransferObjects/EventPayload.php`)

Readonly class wrapping the raw SES event JSON array. Provides clean accessors:
- `eventType(): string`
- `messageId(): string`
- `mailData(): array`
- `sourceEmail(): ?string`
- `subject(): ?string`
- `sentAt(): ?Carbon`
- `timestamp(): ?Carbon` — extracted per event type from the appropriate nested key
- `recipients(): array` — extracted per event type (bounced, complained, delivered, delayed recipients)
- `eventData(): array` — event-type-specific data block
- `bounceType(): ?string` — only for Bounce events

---

## Phase 2: Core Backend

### 2.1 Authentication Middleware (`src/Http/Middleware/OptionalBasicAuth.php`)

If `SES_OBSERVER_AUTH_USERNAME` and `SES_OBSERVER_AUTH_PASSWORD` are configured, challenge with HTTP Basic Auth using `hash_equals()` for timing-safe comparison. If not set, pass through — no auth required.

Registered as `ses-observer.auth` middleware alias by the service provider.

### 2.2 Webhook Controller (`src/Http/Controllers/WebhookController.php`)

Single `store()` method handling `POST /webhooks/{sourceToken}`:
1. Resolve `Source` by token (404 if not found)
2. Verify SNS signature (unless disabled for local/testing)
3. Parse SNS message type:
   - **SubscriptionConfirmation** → `Http::get()` to SubscribeURL, return 200
   - **Notification** → delegate to `ProcessWebhook` action, return 200
   - **UnsubscribeConfirmation** → log, return 200
   - **Unknown** → return 400

Route registered with CSRF and auth middleware exemptions.

### 2.3 Actions (in `src/Actions/`)

**`VerifySnsSignature`**
- Uses `Aws\Sns\MessageValidator` from the AWS SDK
- Configurable: skip when `config('aws-ses-observer.sns_signature_verification')` is false
- Always skip in `local` and `testing` environments
- Returns 403 Forbidden on failure

**`ProcessWebhook`**
- `Webhook::firstOrCreate()` by `sns_message_id` (idempotent)
- Return early if already `processed_at` (duplicate protection)
- Parse the SNS message body JSON
- Construct `EventPayload` DTO
- Delegate to `IngestEvent`
- Mark webhook as processed

**`IngestEvent`**
- `Message::findOrCreateFromPayload()` for the SES message
- Loop each recipient from `EventPayload::recipients()`
- `Event::firstOrCreate()` using the 4-column deduplication key
- Set `bounce_type` for Bounce events
- Increment `events_count` on the message

### 2.4 Background Job (`src/Jobs/DeleteExpiredDataJob.php`)

- Iterates sources with retention policies
- Calls `deleteExpiredData()` on each, using `chunkById()` for large datasets
- Schedule: daily at 4:00 AM via `Schedule::job()->dailyAt('04:00')`
- Queue driver: `database` (works with SQLite and PostgreSQL, no Redis)

---

## Phase 3: UI (Livewire + FluxUI)

### 3.1 Layout & Blade Components

**`resources/views/components/layouts/app.blade.php`** — Main layout with Inter font, Tailwind, FluxUI styles/scripts, dark mode via `prefers-color-scheme`. Header with package name + optional link. Auth warning banner when credentials aren't configured in production.

**Blade Components** (in `resources/views/components/`):
- `source-layout` — sub-layout with breadcrumb + tab navigation (Overview, Activity, Setup, Settings)
- `event-badge` — colored badge per event type
- `color-picker` — radio group for source color selection

### 3.2 Livewire Components (6 full-page, in `src/Livewire/`)

| Component | Purpose |
|-----------|---------|
| `SourceIndex` | Dashboard — lists all sources with 30-day stats (sent count, bounce rate, last activity). FluxUI table. |
| `SourceShow` | Source overview — 6 metric cards (sent, delivered, bounced, complaints, opens, clicks), Chart.js 30-day line chart, bounce breakdown, unique open/click rates. Uses `#[Computed]` for expensive queries. |
| `SourceForm` | Combined create/edit form. FluxUI inputs for name, color picker, retention days. Delete with `<flux:modal>` confirmation (edit mode only). |
| `EventList` | Activity tab — search (`wire:model.live.debounce.300ms`), date range presets, event type checkbox filters with live counts, bounce subtype sub-filters, pagination (50/page), CSV export via `streamDownload()`. All filter properties use `#[Url]` for bookmarkable URLs. |
| `MessageDetail` | Message detail — subject, from/to, tags, SES message ID, chronological event timeline with badges, collapsible raw metadata JSON. |
| `SetupGuide` | AWS setup instructions — step-by-step guide for configuring SES Configuration Sets, SNS Topics, and webhook subscriptions with PHP/Laravel code examples. |

### 3.3 FluxUI Components Used

| UI Element | FluxUI |
|-----------|--------|
| Text inputs | `<flux:input>` |
| Selects | `<flux:select>` |
| Buttons | `<flux:button>` |
| Tabs | `<flux:tabs>` / `<flux:tab>` |
| Tables | `<flux:table>`, `<flux:table.row>`, `<flux:table.cell>` |
| Confirmations | `<flux:modal>` |
| Breadcrumbs | `<flux:breadcrumbs>` |
| Badges | `<flux:badge>` with color variants |
| Search | `<flux:input type="search" icon="magnifying-glass">` |
| Pagination | Livewire built-in |

### 3.4 Routes (`routes/web.php`)

```php
Route::prefix(config('aws-ses-observer.route_prefix', 'ses-observer'))
    ->middleware(config('aws-ses-observer.middleware', ['web', 'ses-observer.auth']))
    ->group(function () {
        Route::get('/', SourceIndex::class)->name('ses-observer.sources.index');
        Route::get('/sources/create', SourceForm::class)->name('ses-observer.sources.create');
        Route::get('/sources/{source}', SourceShow::class)->name('ses-observer.sources.show');
        Route::get('/sources/{source}/edit', SourceForm::class)->name('ses-observer.sources.edit');
        Route::get('/sources/{source}/events', EventList::class)->name('ses-observer.sources.events');
        Route::get('/sources/{source}/messages/{message}', MessageDetail::class)->name('ses-observer.sources.messages.show');
        Route::get('/sources/{source}/setup', SetupGuide::class)->name('ses-observer.sources.setup');
    });

// Webhook — outside auth group, CSRF exempt
Route::post(config('aws-ses-observer.route_prefix', 'ses-observer') . '/webhooks/{sourceToken}', [WebhookController::class, 'store'])
    ->name('ses-observer.webhook')
    ->middleware('web')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
```

### 3.5 Chart.js Integration

Alpine.js component with `wire:ignore`:
- Data passed from Livewire via `@js()` Blade directive
- Dark mode detection via `prefers-color-scheme` media query
- 30-day time series: sent, delivered, bounced
- Chart data grouped by `DB::raw("DATE(event_at)")` (works on both SQLite and PostgreSQL)

### 3.6 Frontend Assets

- **Tailwind CSS** — Inter font, OpenType features, dark mode, pagination styles
- **Gravatar** — helper for email avatar URLs via md5 hash
- **local-time** — JS library for timezone-aware timestamp rendering
- **highlight.js** — PHP + JSON syntax highlighting for setup guide code blocks

All compiled into `resources/dist/` and publishable to `public/vendor/aws-ses-observer/`.

---

## Phase 4: DevOps

### 4.1 No Dockerfile
This is a package, not an app. README documents required PHP extensions (`pdo_sqlite` or `pdo_pgsql`).

### 4.2 CI/CD (GitHub Actions)

Skeleton already includes workflows. Update `run-tests.yml` to add database matrix:
```yaml
matrix:
  include:
    - { database: SQLite, db_connection: sqlite }
    - { database: PostgreSQL, db_connection: pgsql }
```

Jobs:
- **Lint**: Laravel Pint (`composer format -- --test`)
- **Analyse**: PHPStan/Larastan (`composer analyse`)
- **Test**: Pest with dual-DB matrix (`composer test`)

### 4.3 Environment Variables

| Variable | Purpose | Default |
|----------|---------|---------|
| `SES_OBSERVER_AUTH_USERNAME` | Dashboard HTTP Basic auth username | _(none — auth disabled)_ |
| `SES_OBSERVER_AUTH_PASSWORD` | Dashboard HTTP Basic auth password | _(none — auth disabled)_ |
| `SES_OBSERVER_ROUTE_PREFIX` | URL prefix for all routes | `ses-observer` |
| `SES_OBSERVER_VERIFY_SNS` | Enable SNS signature verification | `true` |
| `SES_OBSERVER_DISABLE_AUTH_WARNING` | Hide the "no auth configured" banner | `false` |

---

## Phase 5: Testing (Pest 4)

### Framework
Pest 4 — already configured in the skeleton with `pestphp/pest` ^4.0, `pestphp/pest-plugin-arch` ^4.0, `pestphp/pest-plugin-laravel` ^4.0.

### Conventions
- **Closure-based tests**: `it()` and `test()` syntax, never class-based PHPUnit
- **Expectations API**: `expect()->toBe()`, `->toBeTrue()`, `->toHaveCount()`
- **Datasets**: `->with()` for parameterized tests (e.g., all 10 event types)
- **Hooks**: `beforeEach()` for shared setup within a file
- **Groups**: `->group('webhook', 'ingestion')` for targeted runs
- **Livewire assertions**: `Livewire::test(Component::class)->assertSee()`

### Test Files

| File | Coverage | Patterns |
|------|----------|----------|
| `Feature/WebhookIngestionTest.php` | SNS types, signature verification, idempotency, invalid payloads, missing source | `postJson()`, datasets for SNS message types |
| `Feature/RetentionPolicyTest.php` | Validation rules, scope, deletion logic, cascade behavior | `beforeEach()` with factory setup |
| `Feature/EventSearchTest.php` | Search by email, by subject, partial match, case insensitivity | datasets for search terms |
| `Feature/EventFilteringTest.php` | Date ranges, event types, bounce subtypes, combined OR logic, filter counts | datasets from `EventType` enum cases |
| `Feature/SourceCrudTest.php` | Create, edit, delete via Livewire components | `Livewire::test()` |
| `Feature/CsvExportTest.php` | CSV headers, filtered content, streaming | `streamedContent()` assertions |
| `Unit/EventPayloadTest.php` | Per-event-type extraction of recipients, timestamps, event data | datasets with fixture JSON files |

### Arch Tests

Extend `tests/ArchTest.php`:
```php
arch('models extend Eloquent Model')
    ->expect('codearachnid\AwsSesObserver\Models')
    ->toExtend('Illuminate\Database\Eloquent\Model');

arch('actions have execute method')
    ->expect('codearachnid\AwsSesObserver\Actions')
    ->toHaveMethod('execute');

arch('enums are string-backed')
    ->expect('codearachnid\AwsSesObserver\Enums')
    ->toBeStringBackedEnums();

arch('controllers have suffix')
    ->expect('codearachnid\AwsSesObserver\Http\Controllers')
    ->toHaveSuffix('Controller');
```

### Test Fixtures (`tests/fixtures/`)

Sample SNS/SES JSON payloads sourced from [AWS SES documentation](https://docs.aws.amazon.com/ses/latest/dg/event-publishing-retrieving-sns-examples.html):
- `sns_subscription_confirmation.json`
- `ses_delivery.json`
- `ses_bounce_permanent.json`
- `ses_bounce_transient.json`
- `ses_complaint.json`
- `ses_open.json`
- `ses_click.json`
- `ses_send.json`

### Model Factories (`database/factories/`)
- `SourceFactory.php`
- `MessageFactory.php`
- `EventFactory.php`
- `WebhookFactory.php`

### Running Tests
```bash
composer test                      # All Pest tests
composer test -- --filter=webhook  # By name
composer test -- --group=ingestion # By group
composer test-coverage             # With coverage
composer analyse                   # PHPStan
composer format                    # Pint (fix)
composer format -- --test          # Pint (check only)
```

---

## Best Practices & Standards

### Code Quality (enforce from day 1)
- `declare(strict_types=1);` in every PHP file
- **Laravel Pint** — `composer format` before every commit, Laravel preset
- **PHPStan / Larastan** — start at level 5, aim for level 8
- **Pest arch tests** — enforce naming, prevent debug functions, validate structure

### Package Design
- **No global helpers** — everything namespaced. Blade components, not loose functions.
- **Configurable everything** — route prefix, middleware, table prefix, auth. Override without touching package code.
- **Publishable views** — `php artisan vendor:publish --tag=aws-ses-observer-views` for full customization.
- **No host app assumptions** — don't require a User model, specific auth guard, or middleware.
- **SemVer from 1.0.0** — CHANGELOG.md already in skeleton.

### Database
- **Migrations, not stubs** — use `.php` files with `discoversMigrations()`. Remove the skeleton's `.php.stub`.
- **Table prefix** — `ses_observer_` on all tables, configurable in config.
- **Cascade at DB level** — foreign keys with `cascadeOnDelete()`, not application-level deletes.
- **`json` not `jsonb`** — cross-database compatibility.
- **Indexes in initial migration** — don't add them as afterthoughts.

### Livewire
- **`#[Lazy]`** — on heavy components (SourceShow, EventList)
- **`#[Computed]`** — for expensive queries (dashboard stats, chart data)
- **`#[Url]`** — on all filter properties in EventList (bookmarkable/shareable URLs)
- **`WithPagination`** — Livewire's built-in trait
- **`wire:navigate`** — SPA-like navigation between pages

### Security
- SNS signature verification — never skip in production
- `hash_equals()` — timing-safe credential comparison
- CSRF exemption — only on the webhook route
- Parameterized queries — always, especially for LIKE searches
- `$fillable` — on all models, no `$guarded = []`

### Performance
- **Eager loading** — `with()` on relationship-heavy queries
- **`chunkById()`** — in DeleteExpiredDataJob for large datasets
- **Counter cache** — `events_count` on messages, incremented in IngestEvent
- **Proper indexes** — composite deduplication index, individual indexes on filtered/searched columns

---

## Architectural Decisions

1. **Actions over Services** — `ProcessWebhook`, `IngestEvent`, `VerifySnsSignature` as single-purpose invokable action classes with an `execute()` method
2. **PHP Enums** — native string-backed enums for EventType, BounceType, SourceColor with display logic methods
3. **Livewire full-page components** — all dashboard pages are Livewire components; only the webhook endpoint is a traditional controller
4. **Database queue** — no Redis dependency, works with both SQLite and PostgreSQL
5. **Manual counter cache** — increment `events_count` directly in IngestEvent action (events are only created through one code path)
6. **Webhook in web.php** — CSRF exempted via `withoutMiddleware`, avoids API middleware stack complications
7. **Package-first** — no tight coupling to any host app patterns. Models, routes, views, config are all self-contained.

---

## File Structure

```
src/
  AwsSesObserverServiceProvider.php
  AwsSesObserver.php
  Actions/              ProcessWebhook, IngestEvent, VerifySnsSignature
  Commands/             InstallCommand
  DataTransferObjects/  EventPayload
  Enums/                EventType, BounceType, SourceColor
  Facades/              AwsSesObserver
  Http/Controllers/     WebhookController
  Http/Middleware/       OptionalBasicAuth
  Jobs/                 DeleteExpiredDataJob
  Livewire/             SourceIndex, SourceShow, SourceForm, EventList, MessageDetail, SetupGuide
  Models/               Source, Message, Event, Webhook
  Models/Concerns/      HasRetentionPolicy
config/                 aws-ses-observer.php (publishable)
database/
  migrations/           4 tables (ses_observer_* prefix, publishable)
  factories/            Source, Message, Event, Webhook factories
resources/
  views/components/     layouts/app, header, event-badge, color-picker, source-layout, breadcrumb
  views/livewire/       6 component views
  dist/css/             aws-ses-observer.css
  dist/js/              aws-ses-observer.js (Chart.js, local-time, highlight.js)
routes/                 web.php
tests/
  TestCase.php          Orchestra Testbench base
  Pest.php              Pest 4 config
  ArchTest.php          Architecture tests
  fixtures/             Sample SNS/SES JSON payloads
  Feature/              7 feature test files
  Unit/                 EventPayload tests
```

## Verification Checklist

1. `composer test` passes on both SQLite and PostgreSQL
2. `composer analyse` — no PHPStan errors
3. `composer format -- --test` — no Pint violations
4. POST SNS SubscriptionConfirmation → 200, subscription confirmed
5. POST SES Notification → webhook, message, and event records created
6. POST duplicate notification → no duplicate events (idempotent)
7. Dashboard at `/{prefix}` shows source stats
8. Source overview renders chart with 30-day data
9. Event list: search, date filters, event type filters, CSV export all functional
10. Retention policy: set days, run scheduler, verify old data deleted
11. `php artisan vendor:publish --tag=aws-ses-observer-config` works
12. `php artisan vendor:publish --tag=aws-ses-observer-views` works
13. `php artisan vendor:publish --tag=aws-ses-observer-migrations` works
