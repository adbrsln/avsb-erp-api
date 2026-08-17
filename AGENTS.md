<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

</laravel-boost-guidelines>

---

## Session Memory — Jul 22, 2026

### Project: avsb-erp-api (Laravel 13)
Full migration from Slim 4 (avsb-erp/api/) to Laravel 13 (avsb-erp-api/).

### Migration Status
| Component | Source | Target | Status |
|-----------|--------|--------|--------|
| Controllers | 64 | 66 (+2) | All ported |
| Models | 79 | 79 | Drop-in, identical |
| Services | 27 | 27 | Drop-in, identical |
| Traits | 5 | 5 | Updated PSR-7 → Laravel Request |
| Migrations | 143 | 139 | Converted to Laravel convention |
| Seeders | 56 | 56 | Moved to database/seeders/ |
| API Routes | 399 | 397 | 2 legacy endpoints deprecated |
| Tests | 0 | 125 | Pest feature tests |
| Cron scripts | 5 | 5 | Artisan commands + schedule |

### Key Conversions Applied
- **Auth**: `lcobucci/jwt` → `Laravel Sanctum` (stateless tokens)
- **Middleware**: `JwtAuth` → `auth:sanctum`; `RateLimit` → Laravel `throttle`
- **Controllers**: PSR-7 `Request/Response` → `Illuminate\Http\Request/JsonResponse`
- **Migrations**: `return new class` → `extends Migration`; `$schema->` → `Schema::`
- **Seeders**: `App\Seeds\*` → `Database\Seeders\*`; moved to `database/seeders/`
- **Database**: `Capsule::table()` → `DB::table()`; `Capsule::connection()` → `DB::`
- **Cron**: raw PHP scripts → Artisan commands in `routes/console.php`

### Seeder Commands
- `php artisan db:seed` — initial seeders only
- `php artisan db:seed --class=DummySeeder` — initial + dummy demo data
- `php artisan db:seed --class=BulkSeeder` — initial (skipped) + bulk data

### API Test Suite
- 125 Pest tests across 10 files covering all domains
- Auto-seeds minimal test data via `TestDataSeeder`
- Run: `php artisan test` or `php artisan test --compact`

### Storage
- MinIO (S3-compatible) at localhost:9000 via `FileStorageService`
- Config vars: `STORAGE_DRIVER=r2`, `R2_ENDPOINT`, `R2_BUCKET=avsb-uploads`

### Post-Migration Fixes Applied
- **Factories**: 28 Laravel factories created with Malaysian Faker data
- **HasFactory**: added to all 79 models
- **StaffSeeder**: removed `role` column reference (staff_profiles.role was dropped by migration 022200)
- **ClientSeeder**: `create` → `firstOrCreate` for idempotent re-runs
- **RoadMarkingSeeder**: removed `status` from client data (column doesn't exist on clients table)
- **FileStorageService**: `validateUpload` PSR-7 UploadedFileInterface → Laravel UploadedFile
- **FileStorageService**: `$_ENV` → `config()` for all storage config (R2/MinIO)
- **FileStorageService**: `use_path_style_endpoint` comparison fixed (was `=== 'true'`, now truthy check)
- **ProjectAccess/StaffHelper traits**: PSR-7 ServerRequestInterface → Illuminate Request
- **LogsErrors trait**: base Controller now has `logError()` + `errorResponse()` helpers
- **Exception handler**: all API errors logged with context (url, method, user_id, ip)
- **Route aliases**: added `/audit-logs`, `/settings/company`, `/payroll/me/payslips`, `/staff/me/*`, `/projects/{id}/subcontractors` (GET), `/payments/pending` for frontend compatibility
- **DatabaseSeeder**: Laravel wrapper delegates to AvsbSeeder orchestrator
- **Seeders**: namespace `App\Seeds\*` → `Database\Seeders\*`, moved to `database/seeders/`
- **Migrations**: all 138 converted from `return new class` to `extends Migration` with Schema facade
- **Capsule references**: zero remaining in codebase (all replaced with DB facade)
- **VAPID**: wired through `config/services.php` instead of `$_ENV`
- **PushNotificationService**: installed `minishlink/web-push` v10.1, removed 6 abandoned packages
- **PayrollProcessor**: fixed `$socso24` → `$socso24Amount` typo
- **AttendanceController**: `servePhoto` return type `JsonResponse` → `mixed` (allowed binary Response)
- **Multiple punches per day**: dropped unique(`staff_id`,`date`) on attendance, always creates new records
- **Attendance photo route**: added `/{type}` param to match frontend expectation
- **CI pipeline**: PHP 8.4, SQLite tests, deploy on pass, Telegram on failure
- **Deployment**: nginx.conf, supervisor.conf, deploy README added

### Test Suite
- **125 Pest tests** across 10 files covering all domains
- **TestDataSeeder**: minimal data for auth + core entities
- **SQLite in-memory**: no MySQL dependency for tests
- **Run**: `php artisan test` or `php artisan test --compact`
- **Status**: 116 pass, 9 skip (no seed data), 0 fail

### Production Fixes (applied)
- **MinIO path style**: `env('R2_USE_PATH_STYLE')` returns boolean, `=== 'true'` comparison failed
- **servePhoto TypeError**: binary response vs JsonResponse return type mismatch

---

## Session Memory — Jul 31, 2026 — Attendance Geofence, Schedule Windows, Holidays, Leave Withdraw

### Geofence Punch Gating
- **Migration** `2026_07_31_000000_create_geofences_table.php` — `geofences` (name, description, lat/lng decimal(10,7), radius_meters default 100, is_active, created_by FK) + `attendance.geofence_id` + `clock_out_geofence_id`
- **Geofence model** (Auditable) + **GeofenceService** — `distanceMeters()` haversine, `findContaining()` (nearest active geofence ≤ radius), `accuracyError()` (50m max)
- **GeofenceController** — full CRUD; admin/super_admin write only; read open to all auth'd (punch gate needs it)
- **AttendanceController** — `enforceGeofence()` on clock-in + clock-out: accuracy required ≤50m, lat/lng required, zero active geofences → 422 "No active geofenced sites are configured", outside → 422 "Location is outside all geofenced sites". Proxy punches enforced too. Stores `geofence_id`/`clock_out_geofence_id`. `?presign=1` responses eager-load geofence names
- **Frontend**: `lib/geo.ts` (`haversineMeters`, `findContainingGeofence`, `MAX_PUNCH_ACCURACY_METERS=50`); `GeofencesPage.tsx` admin CRUD (MapPicker center, radius, project badge); Punch.tsx gates photo capture + button behind geofence containment + 50m accuracy
- **Geo validation order in clockOut**: coords → accuracy → geofence → photo (coord/geofence errors precede photo requirement)

### Auto-Geofence from Projects
- **Migration** `2026_07_31_000100_add_radius_to_projects_and_link_geofences.php` — `projects.radius_meters` (default 100) + `geofences.project_id` FK + backfill for existing coords-bearing projects
- **Project model `booted()`**: `saved` → `GeofenceService::syncFromProject()`, `deleted` → deactivate linked geofence. Fires from ALL status paths (ProjectController, PhaseController auto-complete/reopen, seeders)
- **syncFromProject**: coords present → firstOrCreate(project_id), updates lat/lng/name + `is_active = (status === 'active')`; **radius only set on create** (admin edits preserved); no coords → deactivate but keep record
- **Frontend**: ProjectFormPage/ProjectFormDialog radius input; GeofencesPage project-linked geofences show "Project" badge, only radius+description editable

### Schedule Window Detection
- **Migration** `2026_07_31_000200_add_work_schedule_fields.php` — `company_settings.work_start_time/work_end_time` (default), `staff_profiles.work_start_time/work_end_time` (override), `attendance.schedule_flagged` + `schedule_flag_reason`
- **AttendanceController::scheduleFlagReason()** — staff override → company default; no config → no enforcement (opt-in); part-time skipped; ±15m grace; overnight shift support (`end < start` crosses midnight); clock-out preserves clock-in flag (OR)
- **clearFlag** clears schedule flags too; **exportCsv** adds Clock-In/Out Lat/Lng + Schedule Flagged columns
- **Frontend**: StaffFormDialog work start/end time inputs; CompanySettings Statutory default hours; Punch + Attendance orange "Schedule" flag chips
- **`records` endpoint**: added `?all=true` (was silently capped at 100 rows by PaginatedResponse — monthly view dropped records)

### Public Holiday Blocking
- **PublicHoliday model** — `isOn(Carbon)`: recurring matches month-day any year (anchor year 2000), non-recurring exact date+year. Table existed (migration 040400) but was dead code
- **HolidayService** — `holidaysBetween(start, end): array<Y-m-d=>name>` (recurring expansion across year boundary), `isHoliday(date)`
- **LeaveApplication** — `workingDaysCount()` + `getDaysAttribute()` exclude holidays (weekends + holidays skipped, `max(1, count)`)
- **LeaveController::store()** — 422 "Selected dates are all public holidays or weekends" when raw working days = 0 (computed separately since workingDaysCount floors at 1)
- **PublicHolidayController** — CRUD, admin/super_admin write, all-auth read, `year` filter + `all=true`; unique check uses `whereDate` (date cast `2026-07-15 00:00:00` vs string mismatch)
- **PublicHolidaySeeder** — 5 recurring (New Year/Labour/National/Malaysia Day/Christmas, dates anchored 2000) + 4 fixed 2026
- **Frontend**: PublicHolidaysPage admin CRUD; LeaveApplicationDialog fetches holidays, excludes from count, "Holiday: {name}" notices, blocks all-holiday submit (native date inputs can't grey dates — notice-based approach)

### `holidays:fetch` Command
- **FetchPublicHolidays** — `holidays:fetch {--year=} {--state=kuala-lumpur}` scrapes `publicholidays.com.my/{state}/` (browser UA, 30s timeout). Source: publicholidays.com.my — only reliable free MY source (Nager.Date + OpenHolidays lack Malaysia; Google Calendar ICS 500)
- Parses `id="{year}-public-holidays"` table → `Carbon::parse("1 Jan {year}")` + name; upsert-only (`whereDate(date)` + year + non-recurring); **merges shared-date holidays** ("Thaipusam & Federal Territory Day" — 1 Feb 2026)
- Scheduled `yearlyOn(12, 31, '14:00')` (22:00 MY) fetches next year via closure `Artisan::call('holidays:fetch', ['--year' => now()->addYear()->year])`
- Note: `rtk` bash wrapper mangles grep/curl output — use dedicated tools

### Leave Withdraw
- **Migration** `2026_07_31_000300_add_cancel_to_leave_applications.php` — `cancelled_at` + `cancelled_by` FK
- **LeaveController::cancel()** — `POST /leaves/{id}/cancel`: pending → `cancelled` (no balance change); approved → guard `start_date >= today` (else 422 "already started"), restore balance (`used -= days`, `balance = entitled - used + adjusted`), then cancel. Owner or HR/admin only. `LEAVE_CANCELLED` notification
- **NotificationEvent::LEAVE_CANCELLED** constant added (was vestigial in NotificationPrefSeeder) + `leave.cancelled` email template
- **Frontend**: UI copy unified to "Withdraw" (pending row button, drawer button, modal, toasts); `cancelled` status badge secondary

### Deploy Notes
- `php artisan migrate` applies 000100/000200/000300 (000000 ran earlier; 000100+ were missing on live DB causing "Column not found: work_start_time" on punch-out)
- `php artisan db:seed --class=PublicHolidaySeeder` for default holiday list (smoke run already populated 2026 KL holidays)
- Live DB already has 2026 KL holidays from smoke run of `holidays:fetch --year=2026`

---

## Session Memory — Aug 3, 2026 — Geofence Enforcement Toggle

### Geofence Punch Relax Mode (Company Setting)
- **Migration** `2026_07_31_000400_add_geofence_enforced_to_company_settings.php` — `company_settings.geofence_enforced` bool default **true** (strict)
- **CompanySetting model** — `geofence_enforced` in `$fillable` + `boolean` cast
- **AttendanceController::enforceGeofence()** — early-returns `null` when `CompanySetting::value('geofence_enforced', true) === false`; clockIn/clockOut null-safe on `geofence_id`/`clock_out_geofence_id` (stored null when relaxed). Coord range validation unchanged
- **CompanySettingController::update()** — persists via `array_key_exists('geofence_enforced', $body)` (whole-object PUT)
- **GeofenceTest** — `setGeofenceEnforced()` helper + 4 tests: relaxed clock-in (201, null geofence_id), enforced outside→422, relaxed clock-out, toggle persists via PUT
- **Also committed this session**: `POST /notifications/read-all` route (frontend used it, only GET read existed); flaky date-boundary test fixes (LeaveCancelTest weekend drift → fixed dates, AttendanceTest month rollover → exact date range)
- **Verification**: 204 tests / 195 pass / 9 skip / 0 fail; pint clean; frontend `tsc --noEmit` clean

## Session Memory — Aug 3, 2026 — Legacy Invoice Import

### Manual Import + Bulk CSV Command (migration from existing process)
- **Migration** `2026_08_03_000100_add_legacy_source_to_invoices.php` — `invoices.source` (default `system`) + `legacy_document_path`/`legacy_document_filename`/`legacy_paid_date`
- **Invoice model** — 4 fields in `$fillable`; `legacy_paid_date` date cast
- **`LegacyInvoiceImporter` service** — shared logic for UI + command: project resolve (project_id → project_code → project_name), manual invoice number w/ dup check (`withTrashed`) or auto-generate, single total amount (subtotal=amount, sst/retention=0, one "Legacy invoice (migrated)" line item), optional PDF via `FileStorageService::validateUpload` + `putFromFile`. **No JE, no e-invoice, no notification**
- **InvoiceController::import()** — `POST /invoices/import` (multipart); 422 on missing client / amount≤0 / invalid status / dup number. **Route registered BEFORE `invoices/{id}`** (avoid `{id}` catch)
- **InvoiceController::download()** — `source==='legacy'` && doc exists → serve uploaded original (presigned if R2, stream if local); else regenerate via DocumentGenerator
- **E-Invoice guard** — `EInvoiceController::submitInvoice()` 422 `source==='legacy'`. **Pre-existing broken route fixed**: `invoices/{id}/submit-einvoice` pointed at undefined `InvoiceController::submitEInvoice` → now `EInvoiceController::submit`
- **Auto-invoice gate** — `PhaseController` (line ~378) + `InvoiceController::generateForProject()` both filter `where('source','system')` — legacy-only projects can still generate a real invoice
- **`app:import-legacy-invoices` command** (mirrors ImportStaff) — `--file` (default `database/data/legacy-invoices-migration.csv`), `--dry-run`, `--force`, `--missing-project=skip|fail` (default skip, resolved BEFORE import), `--document-dir` (resolves per-row `document` filename). CSV cols: `invoice_number,project_code,project_name,client,amount,status,date,due_date,paid_date,document`. Row-level try/catch, summary Imported/Skipped/Errors, FAILURE exit on errors
- **Frontend** — `LegacyInvoiceImportDialog.tsx` (Import Existing button on InvoicesPage, raw fetch+FormData), "Imported" badge on list+detail, legacy hides Issue/CreditNote/Revert/E-Invoice, keeps Record Payment
- **Tests** — `LegacyInvoiceImportTest` 10 tests: fields/no-JE, dup number 422, auto-number, invalid status/amount 422, PDF store+download, e-invoice 422, generateForProject on legacy-only project, CSV import, dry-run no-op, missing-project skip. Note: `post()` has no files param — use `call('POST', ...)` with `HTTP_AUTHORIZATION` server var; fake `UploadedFile` rejected by finfo — write real minimal PDF bytes
- **Verification**: 214 tests / 205 pass / 9 skip / 0 fail; pint clean; `tsc --noEmit` clean

## Session Memory — Aug 3, 2026 — Superadmin Attendance Exclusion

### Hide super_admin from attendance + counts
- **Root**: `StaffController::index()` already filtered superadmin staff, but `AttendanceController::records()/exportCsv()/summary()` returned all punches — superadmin clock-ins showed in attendance listing
- **Filter pattern** (matches StaffController): `whereDoesntHave('staff.user.roles', fn ($q) => $q->where('role', 'super_admin'))` on the `Attendance` query (relation chain attendance→staff→user→user_roles)
- Applied: `records()`, `exportCsv()`, `summary()` (so `by_staff` grouping + `total_hours` exclude superadmin)
- **DashboardController** — `teamMembers` count + `todayRecords` (presentToday/activeNow) exclude superadmin via same filter; keeps absent/available math coherent
- **ProjectController::costSummary()** + **InvoiceController::calculateProjectCosts()** — labor-cost sums exclude superadmin (add `whereDoesntHave` BEFORE the raw `join('staff_profiles', ...)` — subquery on attendance rows is join-safe)
- **Not touched**: `today()`/self-service, clockIn/clockOut (superadmin can still punch), role-gating (`isPmPlus`/`canViewSummary`), PartTimeController (per-staff detail)
- **Tests** — AttendanceTest `Attendance super_admin exclusion` describe, 5 tests: records?all=true listing, summary by_staff count + total_hours, CSV export lacks name, dashboard presentToday, project labor cost. Float-strict gotcha: PHP json returns int `6`/`200` not `6.0`/`200.0` — assert ints
- **Verification**: 219 tests / 210 pass / 9 skip / 0 fail; pint clean

## Session Memory — Aug 3, 2026 — Reset Projects Command

### `app:reset-projects` maintenance command
- **Purpose**: hard-delete projects + ALL related data (full wipe). Options: `--project=CODE,CODE` (comma-separated, default all), `--status=`, `--dry-run` (preview counts), `--force` (skip confirm). Wrapped in one DB transaction, rollback on error, per-table deleted counts in output. Not scheduled
- **FK graph gotchas (why manual ordered deletion, not `forceDelete` + DB cascade)**:
  - `self_billed_invoices.project_id` has NO onDelete (RESTRICT) → delete before projects or FK error
  - nullOnDelete children survive orphaned: tasks (`phase_id`), invoices/quotations/contracts/attendance/timecards/activity_log (`project_id`) → delete explicitly
  - SoftDeletes child models need `withTrashed()->forceDelete()`: Invoice, Quotation, Contract, ProjectClaim, ProjectClaimDocument, ProjectDocument, SelfBilledInvoice, Timecard, SubcontractorClaimDocument. Plain `delete()` for the rest (Phase/Task/Attendance/ActivityLog/Geofence/etc. have NO SoftDeletes)
  - Pivots via `DB::table()`: task_staff, phase_staff, project_staff_pics, project_project_type, project_project_group
- **Deletion order**: journal_entry_lines → journal_entries (invoice + payment refs, BEFORE invoices) → subcon claim docs → subcon claims → project_subcontractors → project_claim_docs → project_claims → contract_variations → contracts → quotations → invoice_payments → receipts → invoices → attendance → timecards → activity_log → self_billed → geofences → material_usage → project_documents → task_staff → tasks → phase_staff → phase_comments → checklist_results → checklist_items → project_phases → 3 pivots → projects (`withTrashed()->forceDelete()`)
- **JE cleanup**: `journalEntryIds()` builder `select('id')` (subquery in whereIn must return 1 column — else SQLite "sub-select returns 11 columns"); matches `reference_type='invoice'` w/ invoice ids + `reference_type='payment'` w/ invoice OR invoice_payment ids (markPaid refs invoice, storePayment refs payment)
- **Resolution query**: `Project::withTrashed()` so `--project` can target soft-deleted projects
- **Tests** — `ResetProjectsTest` 8 tests (JE orphan cleanup added). Gotchas: ProjectClaim requires `title`; SelfBilledInvoice requires `supplier_id` (FK to clients); project_types requires `code`; Phase has no SoftDeletes; project_types/project_groups tables empty in test DB → `insertGetId` fixtures
- **Verification**: 230 tests / 221 pass / 9 skip / 0 fail; pint clean

## Session Memory — Aug 3, 2026 — Legacy Invoice Import Journal Entries

### JE generation in `LegacyInvoiceImporter::import()`
- Import now runs inside `DB::transaction`. Creates:
  - **Issue JE** (`reference_type='invoice'`, entry_date = invoice date): DR AR(1104) / CR Revenue(4101) = full amount. No SST/retention lines (legacy = 0). Skips w/ warning if 4101/1104 missing (matches doIssue)
  - **Payment JE** (`reference_type='payment'`, entry_date = paid date): DR Bank(1102) / CR AR(1104) = amount_paid. **Throws RuntimeException if 1102/1104 missing** (invoice_payments.debit_account_id/credit_account_id are NOT NULL constrained — silent skip would violate)
  - **InvoicePayment row** (debit=1102, credit=1104, `payment_reference='LEGACY-{number}'`) so detail-page payment history/remaining is correct
- **amount_paid resolution**: paid → defaults to full amount (or provided, capped at amount); partially_paid → required, must be > 0 and ≤ amount; unpaid → 0 (no payment JE, no InvoicePayment)
- **paid_date**: required when amount_paid > 0, defaults to invoice date if blank; stored on `legacy_paid_date`
- **Period lock**: bypassed (consistent with doIssue/markPaid — system JEs don't call `PeriodLockService::assertOpen()`)
- **Frontend**: LegacyInvoiceImportDialog now sends `amount_paid` + `paid_date` for non-unpaid statuses; Amount Paid field (partial marked required, paid placeholder "Full amount"); helper text changed to "Matching journal entries are created"
- **CSV**: `amount_paid` column added (optional, blank = status default); dry-run prints it
- **Tests** — LegacyInvoiceImportTest now 13: paid → issue JE (2 lines, dated invoice date) + payment JE (dated paid date) + InvoicePayment sum; unpaid → issue only; partial → partial payment JE + InvoicePayment; validation 422s (partial w/o amount_paid, amount_paid > amount). Test setup adds `ChartOfAccount 1102` (TestDataSeeder only has 1001/1104/2101/4101/6101)
- **Dev DB**: `php artisan migrate` was pending locally (source column + geofence toggle) — tests use in-memory SQLite so unaffected; dev MySQL needed migration for manual testing
- **Verification**: 230 tests / 221 pass / 9 skip / 0 fail; pint clean; tsc clean

## Session Memory — Aug 3, 2026 — Project Numbering with Client Code

### Project code format change
- **New format** (with linked client): `AV-{client_code}-{YY}{MM}-{SEQ:4}` e.g. `AV-CLT-0001-2608-0001`; fallback (no client match): old `AV-{YY}-{MM}-{SEQ:4}` e.g. `AV-26-08-0001`
- **`NumberingService::generate(string $code, ?string $prefix = null, ?string $pattern = null)`** — added optional overrides, backward compatible (all existing callers unchanged). `{PREFIX}` uses `$prefix ?? $seq->prefix`, pattern `$pattern ?? $seq->pattern`. Counter/reset logic untouched → **global sequence across clients** (no per-client reset)
- **New `NumberingService::generateProject(?string $clientCode)`** — helper; passes `AV-{code}-` prefix + `{PREFIX}{YEAR}{MONTH}-{SEQ:4}` pattern when client present, explicit `AV-` + `{PREFIX}{YEAR}-{MONTH}-{SEQ:4}` fallback otherwise. **Important**: fallback MUST pass explicit `'AV-'` prefix — auto-created sequence row uses `defaultPrefix()` = `PRO-` (from `strtoupper(substr('project',0,3))`), giving `PRO-26-08-0001` otherwise
- **`ProjectController::store()`** — resolves `$clientCode` from `client_id` (find) or `client` name (company_name match) BEFORE generating code (was generated before client resolution); uses `generateProject`
- **Seeders updated**: `BulkProjectSeeder`, `MillPaveSeeder` (TNB), `RoadMarkingSeeder` (reordered: client firstOrCreate BEFORE project code gen), `ExtraProjectSeeder` (nullable client → fallback path). `NumberingSequenceSeeder` project description updated
- **Client codes**: `clients.client_code` nullable; some seeded manual (`TNB`, `CLT-MBPJ-001`) — flow into prefix as-is. `project_code` col len 50 — new format 20 chars, safe
- **Not affected**: frontend (project_code display-only), legacy-invoice CSV import (matches user-entered codes), reset-projects `--project=` filter
- **Tests** — ProjectTest `Project numbering` describe, 4 tests: client-id format regex `^AV-{code}-\d{4}-\d{4}$` (YYMM = 4 digits!), name-match client, legacy fallback `^AV-\d{2}-\d{2}-\d{4}$`, global counter distinct per client + increments
- **Verification**: 234 tests / 225 pass / 9 skip / 0 fail; pint clean


## Session Memory — Aug 5, 2026 — TNB Purchase Order Seeder + Import Command

### TNB PO tracker CSV → ERP import (seeder + command)
- **Purpose**: migrate TNB client PO tracking spreadsheet into the ERP. Row = TNB PO → project → AVSB invoice → payment → PO confirmation phase.
- **`TnbPurchaseOrderSeeder`** (`database/seeders/TnbPurchaseOrderSeeder.php`) — shared row-import engine, NOT in AvsbSeeder chain (real client data, standalone only):
  - `columnMap(array $header): array` — static, normalizes header → snake_case key → col index. **First-wins** (populated `DATE_PAID` wins over trailing empty `DATE _PAID`; both normalize to `date_paid`)
  - `processRow(array $row, array $cols, string $poNumber): void` — one row in its own `DB::transaction`; throws `RuntimeException` on row-level failures
  - `run(?csvPath)` — reads `database/data/tnb-purchase-orders.csv`, graceful skip message when file missing (MillPaveSeeder precedent)
- **`app:import-tnb-purchase-orders` command** (`app/Console/Commands/ImportTnbPurchaseOrders.php`) — options `--file=` (default `database/data/tnb-purchase-orders.csv`), `--dry-run` (per-row preview, zero writes), `--force` (skip confirm). `RuntimeException` → skipped, `\Throwable` → error, exit FAILURE when errors > 0. Mirrors `app:import-legacy-invoices` UX.
- **CSV column mapping** (user-clarified semantics):
  - `CLIENT` = **client code** → resolved via `clients.client_code`; missing → row error (client master must exist; TNB from `ClientSeeder`)
  - `TNB_PIC` = match `ClientPIC` by `(client_id, name)` → `projects.client_pic_id`; no match → null (no auto-create)
  - `PO_CONFIRMATION` = **Phase name** on project; `DATE_SE` present → `status=completed` + `completed_at=DATE_SE 17:00:00`; empty → `status=pending`; idempotent via `(project_id, name)` check
  - `INV_AVSB` = **existing AVSB invoice number** → find by `invoice_number` (withTrashed), pair `project_id`/`client_id` (only if null), set `status=paid`, record payment via `recordPayment(..., 'TNB-{poNumber}')`. Not found → row error
  - `INVOICE` (when INV_AVSB empty) = create new invoice via `LegacyInvoiceImporter::import()`: number from CSV (empty → auto `INV-`), amount = `PELARASAN` fallback `PO_AMOUNT`, `amount_paid` = `TOTAL_PAID` (0 → full), `source=legacy`
  - `SUBCON`/`SUBCON_FEE`/`MAINCON`/`MAINCON_FEE`/`DEDUCTION`/`BALANCE_PAYMENT`/`INV_SUBCON`/`INV_DATE` → JSON extras in `projects.description` (MillPaveSeeder pattern)
  - `STATUS` → ignored. `IS_PROCEED` ≠ TRUE → row skipped
- **`LegacyInvoiceImporter::recordPayment()` refactor** — old private `createPayment()` → public `recordPayment(Invoice, float, ?string, string $reference='')` with `payment_reference` idempotency guard (existing payment ref → no-op). `createPayment` delegates. Backward compatible; LegacyInvoiceImportTest 13 still green.
- **Date format gotchas** (CSV mixes formats — column-aware parsing only):
  - `DATE`/`INVOICE_DATE`/`DATE_SE` = `dd/mm/yyyy` (also accepts `dd.mm.yyyy`)
  - `DATE_PAID` = US `m/d/yy` (e.g. `4/17/25` → 2025-04-17); 2-digit year assumes m/d/yy, 4-digit falls back to d/m/Y
- **Test gotcha**: `TestDataSeeder` seeds ≥1 project + 0 invoices → NEVER assert global `Project::count()`; assert `where('po_number', ...)` scoped. Test file adds `uses(RefreshDatabase::class)` (top-level + inside each describe — Pest describe scope doesn't inherit top-level uses).
- **Verification**: 247 tests / 238 pass / 9 skip / 0 fail; pint clean; frontend repo untouched (tsc N/A). Commits `3c672e5`→`54b4e3d` (5 commits).
- **User flow**: drop real rows into `database/data/tnb-purchase-orders.csv` → `php artisan app:import-tnb-purchase-orders --dry-run` → `--force`.

## Session Memory — Aug 6, 2026 — TNB PO Seeder (shared invoices, subcon bills, phases), Phase Maintenance, Auto-invoice removal

### TNB Purchase Order Seeder — final semantics (`TnbPurchaseOrderSeeder` + `app:import-tnb-purchase-orders`)
- **CSV layout now 29 meaningful cols**: header has `PHASE_STATUS` + `PHASE_STATUS_REMARKS` inserted after `PROJECT_STATUS`. Header normalization = first-wins snake_case (` IS_PROCEED` leading space + BOM tolerated). `DATE_PAID` (populated) wins over trailing `DATE _PAID`.
- **Dates are mixed-format**: rows use both `d/m/Y` (e.g. `21/01/2025`) AND US `m/d/yy` (e.g. `10/31/25`, `6/23/26`). ONE parser `parseFlexibleDate()` handles both: 2-digit year → m/d/yy; 4-digit → d/m/Y (also dd.mm.yyyy). Used for DATE, INVOICE_DATE, DATE_SE, DATE_PAID, INV_DATE.
- **Invoice dual path**:
  - `INV_AVSB` populated → find existing invoice. Exists → **pair** (same document; if owned by another project it is SHARED via `invoice_project` pivot, each PO appended as line item `PO {po} — {project name}`, invoice total/subtotal = sum of items). Not exists → create legacy with INV_AVSB as invoice_number.
  - `INVOICE` populated (INV_AVSB empty) → create legacy with that number.
  - Both empty → **auto-number ONLY when `PAYMENT_STATUS ∈ {PAID, PARTIALLY_PAID}`** — UNPAID/CANCELLED rows get NO invoice (project + phases only). Auto path idempotent: skip if project already has legacy invoice.
  - `createLegacyInvoice`: invoice exists → **skip silently (return), NOT throw** — throwing rolled back the whole row's transaction (killed subcon billing/claims). Item always rewritten to `PO {po} — {name}` format.
- **Shared invoice (multi-project)**: migration `2026_08_06_000000_create_invoice_project_table` — pivot `invoice_project` + backfill from `invoices.project_id`. Models: `Invoice::projects()` belongsToMany, `Project::sharedInvoices()`, `Project::allInvoices()` (own + shared, deduped). `invoices.project_id` KEPT as primary owner (legacy contract). `ProjectController::show` invoices = own + shared (distinct).
- **Payment on shared invoice = SINGLE, full-total**: `pairExistingInvoice` records payment only if none exists; amount = invoice total (grows as items append); payment JE lines updated to match (`updatePaymentJournalEntries` DR 2101? no — DR bank/AR). Invoice marked paid. Per-PO payments NOT recorded (single payment rule).
- **Subcon invoice = AP bill (industry practice)**: `createSubcontractorBilling` — Subcontractor master + Vendor master code `EB`, `ProjectSubcontractor` (status → `completed`), **Bill** `bill_number = INV_SUBCON` (existing subcon claim/invoice number), receive JE DR 5101 / CR 2101, BillPayment JE DR 2101 / CR 1102, bill → paid. Plus **SubcontractorClaim** (claim_number = INV_SUBCON, claimed_amount = fee, status paid, paid_at = DATE_PAID, full workflow timestamps, work_done 100%). Claim created BEFORE bill guard (bill-exists early-return must not skip claim). Fee = `DEDUCTION` if >0, else `SUBCON_FEE %` (e.g. `25%`) × invoice amount, else numeric fee. Claim + bill both idempotent (unique numbers).
- **PHASE_STATUS/PHASE_STATUS_REMARKS**: PHASE_STATUS names the CURRENT phase (values seen: `JMS`, `SE`, `Invoice Submission`). `applyPhaseStatus`: target phase (exact OR substring name match, e.g. `SE` → `Service Entry (SE)`) → `in_progress` + `started_at` (start date 08:00) + remarks → phase `description`; all phases BEFORE it (order <) → `completed` + `completed_at` (end date 17:00); later → pending. Also stored as project description JSON extras (`phase_status`, `phase_status_remarks`) on create + backfill.
- **DATE_SE**: `applyStandardPhaseCompletion` — SE phase (name ends `(SE)`) → `completed` at DATE_SE 17:00; all prior standard phases → completed at their end_date 17:00. No DATE_SE → pending.
- **Standard phase set (11, from CreatesStandardPhases defaultPhases)**: PO Confirmation, Site Visit, Project Implementation (Mill and pave), Coring Test, Lab Report, Road Marking, Joint Measurement Sheet (JMS), Laporan Kerja Siap (LKS), Service Entry (SE), Invoice Submission, Payment Settlement (30 days). TNB phase REMOVED. `createConfirmationPhase` guard must check created name `'PO Confirmation'` (NOT the CSV value) — else re-run duplicates the phase.
- **Extras JSON on project.description**: tnb_station, phase_status, phase_status_remarks, po_confirmation, po_amount, pelarasan, date_se, subcon, subcon_fee, maincon, maincon_fee, deduction, balance_payment, inv_subcon, inv_date.
- **ProjectGroup**: station → `ProjectGroup` (name = station, random dark color), linked via `$project->groups()->sync()`. Fixed `firsOrCreate` → `firstOrCreate` typo. Guard empty station.
- **CSV state**: 121 rows, 96 TRUE / 25 NOT TRUE (24 FALSE + 1 comment). FALSE rows = pending-adjustment POs (PHASE_STATUS=PELARASAN etc.) + 42252315/42252319 (reverted by user after being imported — bills EB1522/EB1523 + claims + invoices remain in DB) + 42301263/01318 (typo station PUTRAYAJA, fixed pelarasan). **64 TRUE rows are UNPAID w/o invoice ref → no invoice created (correct now); earlier imports created ~64 wrong PAID auto-invoices → cleanup pending (offer stands: maintenance command w/ dry-run).**
- **Tests**: `TnbPurchaseOrderSeederTest` (26), `CreatesStandardPhasesTest` (13). Test gotcha: `TestDataSeeder` seeds ≥1 project + 0 invoices → assert po_number-scoped counts, never global `Project::count()`. Pest describe needs explicit `uses(RefreshDatabase::class)` inside block. V2 CSV helper (29-col header) for PHASE_STATUS tests.

### Phase Maintenance command (`app:phase-maintenance`)
- Attribute-style signature `#[Signature]`/`#[Description]`. Options `--status= completed|pending`, `--projects=` comma codes, `--po=` exact po_number, `--phase=` name, `--force`. Maintenance-only: phase status + timestamps; NO project-status sync, NO invoice/JE.
- Interactive: status select → project select (searchable `multisearch` over code/name/po_number, top 50) OR direct codes → phase select (distinct names, "All phases") → confirm. **`scripted()` = any flag present → skip ALL prompts** (tests + CI). `laravel/prompts` uses GLOBAL functions `\Laravel\Prompts\select/multisearch/text`, NOT `Prompt::` static methods (v0 here).
- `completed` → status + completed_at (existing or now); `pending` → clears completed_at/completed_by/started_at/started_by. Idempotent (already-target skipped).
- Bug fixed: "All phases" selection — `Collection::prepend($value, $key)` arg order; `--phase=All phases` → treated as all.

### Auto-invoice on project completion — REMOVED
- `PhaseController::complete`: phases all done → project `completed` + `PROJECT_COMPLETED` notification ONLY. The invoice auto-create block (Invoice::create + JE DR 1104/CR 4101) deleted. Invoice creation = manual only (`POST /projects/{id}/generate-invoice`, quotation/contract generation, UI form). User decision: invoice creation requires user intervention.

### LegacyInvoiceImporter
- `recordPayment(Invoice, float, ?string, string $reference='')` — public, idempotency guard on `payment_reference`; `createPayment` delegates. Used by TNB seeder shared-invoice payments.

### Frontend (avsb-erp)
- `Projects.tsx`: search debounce 350→600ms + no full-page spinner on refetch (keep list mounted). Browser-verified: one request on fast typing.
- `RootLayout.tsx`: change-password `api.post` → `api.put` (backend accepts both via `Route::match(['put','post'])`).
- `LockScreen.tsx`: password `autoComplete` → `new-password`.

### Cavecrew subagents
- Model pins fixed haiku → opencode-go/deepseek-v4-flash (restart required). See ~/.config/opencode/AGENTS.md.

### Verification
- Latest: 284 tests / 275 pass / 9 skip / 0 fail; pint clean. Commits this session: c889044 → eb7a1b7 (13 commits) + frontend 52bd1fd + 10377c8 + 3b702a7.

## Session Memory — Aug 10, 2026 — Optional Statutory Deductions (EPF/SOCSO/EIS)

### Per-staff opt-out, default ON
- **Migration** `2026_08_10_000000_add_socso_contributing_to_staff_profiles.php` — `staff_profiles.socso_contributing` bool default **true** + active-staff NULL backfill (mirror 041200 pattern)
- **Migration** `2026_08_10_000100_set_eis_contributing_default.php` — `eis_contributing` default **true** + NULL backfill (column existed nullable, was dead code)
- **StaffProfile** — `socso_contributing` added to fillable + boolean cast
- **`PayrollProcessor::process()`** — **REMOVED `where('epf_contributing', true)` filter** (non-contributors were excluded from the run entirely → no pay item → no payslip). All active staff now get pay items. Per-staff gates: `epf_contributing ? EPFCalculator : new EPFResult(determined schedule, 0, 0)`; socso/eis use zero-result equivalents; SKBBK now gated on `socso_contributing && socso_24h_enabled`
- **epf_schedule_code FK gotcha** — column is NOT NULL FK → `epf_schedules.code`; storing 'NONE' violates. Opt-out keeps `(new ScheduleDeterminer)->determine($employee)` code (A/C/D/FLAT) with **zero amounts** — schedule shown but amounts 0
- **`PayrollController::recalculateStatutory()`** — same gates on earnings adjustment. Gotcha: citizenship for `calculateRaw` derived from `nationality` (str_contains 'Malaysian' → citizen), NOT the `citizenship` field — staff with non-Malaysian nationality hit FLAT
- **Factory/`ImportStaff`/`MalaysianDataGenerator`** — `socso_contributing` true (ImportStaff: derived from `socso_no` presence, mirrors epf pattern); factory `eis_contributing` fixed true (was random 80%)
- **Frontend** — SOCSO Contributing checkbox in `StaffFormDialog` (default ON) + `StaffDetailPanel` row + `types.ts`. EPF/EIS toggles pre-existed (unchanged)
- **Tests** — `PayrollProcessorTest` (11): per-flag opt-outs, all-three-off, SKBBK gate, recalc respects flags, full process→confirm→mark-paid→payslip download
- **Test gotchas**: EPF FLAT seeded **2%/2%** by migration 014000 (NOT 12%) — use non-citizen + non-PR + not-elected staff for deterministic FLAT; add Socso/EisContributionTier covering salary range in beforeEach; **Sanctum guard caches authenticated user across requests within ONE test** (first token wins) → split owner-scoped download test into its own test; downloadPayslip is owner-scoped (403 if requester's staff id ≠ item.employee_id)
- **Broken 2-arg convenience routes (pre-existing, dead)**: `/payroll/items/{id}/confirm`, `/payroll/items/{id}/mark-paid` → controller methods need 3 args; `/payroll/items/{itemId}/recalculate` → method `recalculateItem` doesn't exist. FE uses 3-arg routes (`/payroll/periods/{id}/items/{itemId}/...`) — unaffected. Fix on request
- **Verification**: 302 tests / 293 pass / 9 skip / 0 fail; pint clean; FE `tsc --noEmit` clean. Commits: b96ac4d (API), 90b0800 (FE)

## Session Memory — Aug 17, 2026 — Attendance Auto Clock-Out

### Feature
- **Purpose**: auto-close open attendance sessions when staff forget to clock out, at each staff's scheduled `work_end_time` + configurable grace. Flagged (`auto_closed`) for HR review, never silent.
- **Command** `attendance:auto-clock-out` (`app/Console/Commands/AutoClockOut.php`) — options `--dry-run` (preview, zero writes), `--grace=` (override). Self-gated: `CompanySetting::value('auto_clock_out_enabled')` false → info + SUCCESS. Scheduled in `routes/console.php`: **everyFiveMinutes()** (self-gating makes always-scheduled safe).
- **Close time = `work_end + grace`, NEVER `now()`** — prevents OT cut (Employment Act 1955). OT/correctness handled via re-punch (multiple punches/day) or PM/HR adjust.
- **Window resolution**: staff `work_end_time` override → `company_settings.work_end_time` default → none → **skip** (opt-in, mirrors `scheduleFlagReason()`).
- **Skips**: `worker_status == 'part_time'` (matches scheduleFlagReason; per-project billing must not auto-close).
- **Overnight shifts**: `resolveCloseAt()` — build `Carbon::createFromFormat('H:i', end, 'Asia/Kuala_Lumpur')` + grace → `->utc()`; if `<= clock_in` add a day (close next morning). Verified by test (22:00→06:00 shift, 9h).
- **Race guard**: re-read `Attendance::whereKey(id)->whereNull('clock_out')->first()` before update — manual clock-out wins, sweep skips (no photo/coords overwrite).

### Schema / models
- Migrations: `2026_08_17_000000` → `attendance.auto_closed` (bool, default false, after `schedule_flag_reason`) + `auto_close_reason` (string nullable) + `auto_closed_at` (timestamp nullable). `2026_08_17_000100` → `company_settings.auto_clock_out_enabled` (bool default false) + `auto_clock_out_grace_minutes` (unsignedInt default 60).
- `Attendance` + `CompanySetting`: fillable + casts (`auto_closed` bool, `auto_closed_at` datetime, `auto_clock_out_enabled` bool, `auto_clock_out_grace_minutes` integer).
- `CompanySettingController::update()` — new keys in **BOTH** branches (`array_key_exists` in update branch; create branch defaults `?? false` / `?? 60`). Create-branch omission = settings silently dropped when no row exists (caught by PUT test).

### Controller changes (`AttendanceController`)
- **L1 stale-close upgrade** (`clockIn()`, previous-day open session): now sets `auto_closed=true`, `auto_close_reason='stale_session'`, `auto_closed_at` + queues `attendance.auto-closed` notification. Sweep normally preempts L1 (closes before next-day clock-in).
- **clockOut guard**: `$record->auto_closed` → 422 `"Your session was auto-clocked out at {H:i MY}. Clock in again if you are still working."` (old generic "Already clocked out").

### Notifications
- `NotificationEvent::ATTENDANCE_AUTO_CLOSED = 'attendance.auto-closed'` + template in `NotificationTemplateSeeder` (context: `date`, `clock_in`, `clock_out` MY times, `hours`, `url`).
- **Gotcha**: `NotificationTemplateSeeder` is a PLAIN class (no `extends Seeder`) — CANNOT run standalone via `php artisan db:seed --class=...` (`setContainer()` undefined). Seed via `php artisan tinker --execute '(new Database\Seeders\NotificationTemplateSeeder)->run();'` or full `db:seed`. `NotificationService::queue()` silently no-ops (returns null) without template row.
- **TZ gotcha (pre-existing, NOT fixed)**: `config('app.timezone')` hardcoded `'UTC'` (`config/app.php:68`, no `env()`). Stored timestamps UTC. Command does window math in `Asia/Kuala_Lumpur`, converts to UTC. **`scheduleFlagReason()` compares UTC time-of-day vs MY-configured windows → off-by-8h inconsistency in prod; sweep uses correct MY math. Separate bugfix offered, out of scope.**

### Frontend (avsb-erp)
- `types.ts` — `AttendanceRecord` += `auto_closed`/`auto_close_reason`/`auto_closed_at`; `CompanySetting` += `auto_clock_out_enabled`/`auto_clock_out_grace_minutes`.
- `Attendance.tsx` — "Auto" badge (`Badge variant="secondary"`, tooltip explains reason) on today completed rows + monthly expanded rows.
- `Punch.tsx` — amber "Session auto-closed at {time}. Clock in again if still working." banner (after schedule-flag block); `isWorking` false → Clock In available (recovery path).
- `CompanySettings.tsx` — "Auto clock-out at end of work hours" checkbox + Grace minutes input (shown when enabled; mirror geofence toggle markup).

### Tests
- `AutoClockOutTest.php` (12): disabled no-op, closes past end+grace (asserts exact `clock_out` UTC + `total_hours`), window intact, part-time skip, no-work-times skip, overnight (23:00 UTC close, 9h), dry-run no-writes, idempotent re-run, notification queued, 422 auto-closed message, stale-session L1 flags, settings PUT persist. Helpers: `autoCloseStaff()` (staff + work times), `enableAutoClose($grace)`, `openSession($staff, $clockInUtc)`; `beforeEach` creates `attendance.auto-closed` template defensively.
- **Test timing**: work window math — 08:00 MY = 00:00 UTC, 17:00 MY = 09:00 UTC, +60m = 10:00 UTC. Set `Carbon::setTestNow` in UTC.
- **Verification**: 314 tests / 304 pass / 9 skip / **1 pre-existing flake** — `LeaveCancelTest "withdraws an approved leave"` fails 422 "already started" (fixed leave dates from Aug 3 fix now past real date Aug 17; environmental date-drift, clean-tree + isolated fail proves NOT this feature). Pint clean; FE `tsc --noEmit` clean. API commits: 43e6f04, 8046277, a321689; FE commits: f21b2c5, 02299c6.
