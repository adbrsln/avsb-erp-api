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
- **Deletion order**: subcon claim docs → subcon claims → project_subcontractors → project_claim_docs → project_claims → contract_variations → contracts → quotations → invoice_payments → receipts → invoices → attendance → timecards → activity_log → self_billed → geofences → material_usage → project_documents → task_staff → tasks → phase_staff → phase_comments → checklist_results → checklist_items → project_phases → 3 pivots → projects (`withTrashed()->forceDelete()`)
- **Resolution query**: `Project::withTrashed()` so `--project` can target soft-deleted projects
- **Tests** — `ResetProjectsTest` 7 tests. Gotchas: ProjectClaim requires `title`; SelfBilledInvoice requires `supplier_id` (FK to clients); project_types requires `code`; Phase has no SoftDeletes; project_types/project_groups tables empty in test DB → `insertGetId` fixtures
- **Verification**: 226 tests / 217 pass / 9 skip / 0 fail; pint clean

