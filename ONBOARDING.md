# CHRSD CMS — Team Onboarding

**Welcome.** This project is a production-grade verifiable-document management system built for the Centre for Humanitarian Research and Social Development Foundation (CHRSD). It's Laravel 11 + Filament v3, with a full HR + document-issuance backbone (Employees, Certificates, ID Cards, Official Letters), cryptographic verification, an employee self-service portal, a public verify kiosk, and Sanctum-authenticated API endpoints.

If you're picking this up from Ed (`ed@chrsd.org`), this doc gets you productive in about 15 minutes.

---

## 1. What's already built

**23 steps of incremental delivery.** The full change log lives in the memory file `project_chrsd_cms.md` (see §5). At a glance:

| Step | What landed |
|---|---|
| 1 | Verification backbone — `HasVerification` trait + `VerifiableObserver` (per-year atomic serial + HMAC-SHA256 hash keyed to `APP_KEY`) |
| 2 | Domain schema — Departments, Positions, Employees, Certificate Types, Certificates, Letter Categories, Official Letters |
| 3–4 | Filament CRUD resources + issuance workflow actions (Generate PDF, Revoke, Mark Delivered) |
| 5 | Dashboard widgets — employee stats, document stats, issuance chart, recent activity |
| 6 | Filament Shield — 4 roles (`super_admin`, `hr_manager`, `hr_staff`, `viewer`), 94 permissions auto-generated |
| 7 | Bulk cert issuance + admin verification lookup page + rate-limited public `/verify/{hash}` |
| 8 | Reports (Department Roster, Monthly Issuance, Revocation Register) + queued notifications on delivery/revocation |
| 9 | Employment history — observer captures promotions/transfers automatically; Service Record PDF report |
| 10 | Employee **portal** (2nd Filament panel at `/portal`) + certificate request workflow |
| 11 | Request lifecycle notifications + **compliance bundle** PDF with HMAC chain-of-custody manifest |
| 12 | PHPUnit test suite — 100+ tests locking down every contract |
| 13 | Multi-tenancy — `organizations` table, `BelongsToOrganization` trait, session-scoped queries, tenant switcher |
| 14 | CI (GitHub Actions), Docker Compose stack, Filament clusters + global search, enforced tenant middleware |
| 15 | Onboarding wizard (super_admin creates new orgs) + cross-org dashboard widget |
| 16 | **PDF content signing** — every generated PDF gets an HMAC signature stored; `/api/verify/pdf` accepts uploads |
| 17 | **Kiosk mode** at `/verify/kiosk` (reception-desk UI, 3 input methods) + branded email theme |
| 18 | **Two-factor auth** — TOTP (RFC 6238, no external deps), recovery codes, forced challenge middleware |
| 19 | **API tokens** — Sanctum with audit columns (last_used_ip/UA), auth-aware rate limiter (1000/min vs 30/min anon) |
| 20 | Password policy (12+ mixed chars) + forced-change flow + admin "Reset password" row action |
| 21 | OpenAPI 3.1 spec at `/api/openapi.json` + Swagger UI at `/api/docs` |
| 22 | **ID Cards** — CR80 landscape, dual-side PDF (front+back), CHRSD-branded certificate + letter templates using the real brand palette (`#123420` green, `#C09020` gold, `#EFEDE6` warm ground) |
| 23 | **Document expiry monitoring** — nightly `documents:expiry-scan` command (07:00, `Schedule::command()`), 3-notice cadence per doc (SOON at T−30, URGENT at T−9, EXPIRED at T+1) with 21-day anti-spam guard, auto-flip to `Expired`, HR broadcast, `/admin/expiry-dashboard` UI + widget |

**Test suite**: `117 tests / 469 assertions / ~37 seconds` on this machine.

---

## 2. Getting started (Windows dev)

```bat
cd C:\Projects\CMS
start.bat
```

That spawns three windows:
- **HTTP** — `php artisan serve` on `127.0.0.1:8000`
- **Queue** — `queue:listen` worker
- **Vite** — asset builds (if you're editing CSS/JS)

Then in a browser:
- **Admin**: http://127.0.0.1:8000/admin — `admin@chrsd.org` / `password`
- **Portal**: http://127.0.0.1:8000/portal — `employee@chrsd.org` / `password`
- **Kiosk**: http://127.0.0.1:8000/verify/kiosk
- **API docs**: http://127.0.0.1:8000/api/docs
- **Expiry dashboard**: http://127.0.0.1:8000/admin/expiry-dashboard

## 3. Getting started (Docker — recommended for staging)

```bash
docker compose up -d --build
```

Same URLs but on port `8080`. See `docker/README.md` for details. Container image includes PHP 8.3 + Chromium + Node, so no `-d extension=…` gymnastics.

---

## 4. Two Windows-specific gotchas you MUST know

### 4.1 PHP extensions

`C:\php\php.ini` on Ed's machine has `gd`, `curl`, `pdo_mysql`, `sqlite3` **disabled by default**. Every `php artisan …` command in this project needs:

```
php -d extension=gd -d extension=curl -d extension=pdo_mysql -d extension=sqlite3 artisan …
```

`start.bat` inlines this for you. On a fresh clone, either:
- Enable those four extensions permanently in `C:\php\php.ini`, or
- Always use `start.bat` / `docker compose`

Docker sidesteps this entirely.

### 4.2 Composer 2.10+ security-advisory blocker

Laravel 11 and Browsershot 4 ship with active security advisories that Composer 2.10 refuses to install by default. Every `composer require` in this project uses `--no-security-blocking`. See the CI workflow at `.github/workflows/tests.yml` for the pattern.

---

## 5. Memory / context — how to pick up where I left off

Ed used Claude Code's persistent memory system. Three files under `C:\Users\user\.claude\projects\C--Projects-CMS\memory\`:

- **`project_chrsd_cms.md`** — the definitive project state. Every step from 1 through 23 is documented here with the exact class names, method signatures, file paths, and rationale.
- **`chrsd_cms_runbook.md`** — how to launch + DB toggle (SQLite ↔ MySQL) + demo credentials.
- **`chrsd_cms_php_workaround.md`** — the `-d extension` and `--no-security-blocking` context above.

When you (or another Claude instance) open the project, those files auto-load. **You should be able to say "continue from step 23" and everything just works.**

If you're a human colleague coming in fresh: **read `project_chrsd_cms.md` end-to-end first**. It's ~230 lines and it's the fastest way to understand the architecture.

---

## 6. Where things live (mental map)

```
app/
├── Concerns/               HasVerification, BelongsToOrganization
├── Console/Commands/       ScanDocumentExpiryCommand
├── Enums/                  VerificationStatus, EmployeeStatus, CertificateIssuance, IdCardIssuance, …
├── Filament/
│   ├── Clusters/           OrgUnit, Documents
│   ├── Resources/          Employee/Department/Position/Certificate/OfficialLetter/IdCard/…
│   ├── Pages/              VerificationLookup, OnboardOrganization, TwoFactorAuthentication,
│   │                       ChangePassword, Reports/DepartmentRoster, Reports/ComplianceExport,
│   │                       Reports/ExpiryDashboard …
│   ├── Widgets/            EmployeeStats, DocumentStats, IssuanceTrendChart,
│   │                       CrossOrganizationStats, ExpiringDocuments
│   └── Portal/             Second Filament panel (MyProfile, MyCertificates, MyRequests)
├── Http/
│   ├── Controllers/        VerificationController, PdfVerificationController,
│   │                       KioskVerifyController, OpenApiController, …
│   └── Middleware/         SetCurrentOrganization, EnsureTwoFactorPassed,
│                           RequirePasswordChange, ResolveSanctumToken, TrackApiTokenUsage
├── Models/                 Employee, Certificate, OfficialLetter, IdCard, Organization,
│                           EmploymentEvent, CertificateRequest, PersonalAccessToken, …
├── Notifications/          CertificateDelivered, DocumentRevoked, CertificateRequestApproved,
│                           DocumentExpiring, DocumentExpired, …
├── Observers/              VerifiableObserver, EmployeeStateObserver
└── Services/
    ├── Auth/               TotpService
    ├── Documents/          CertificateGeneratorService, LetterGeneratorService,
    │                       IdCardGeneratorService, PdfSignatureService,
    │                       CertificateRequestService, BulkCertificateIssuanceService,
    │                       ExpiryScannerService
    ├── Onboarding/         OnboardingService
    ├── Reports/            ReportService
    └── Verification/       VerificationService, QrCodeService

routes/console.php          Schedule::command('documents:expiry-scan')->dailyAt('07:00')

config/                     openapi.php, cms.php, filament-shield.php
database/migrations/        (30+ migrations, chronological)
resources/views/
├── documents/              certificates/, id_cards/, letters/, reports/  (PDF Blade templates)
├── filament/               pages/, portal/pages/                          (Filament page views)
├── kiosk/index.blade.php   (public kiosk UI)
├── openapi/docs.blade.php  (Swagger UI)
└── vendor/mail/html/themes/chrsd.css  (branded email theme)

tests/Feature/              18 test files covering every contract
```

---

## 7. Run the tests

```bat
php -d extension=gd -d extension=curl -d extension=pdo_mysql -d extension=sqlite3 artisan test
```

Expected: **117 passed (469 assertions)**, ~37 seconds against SQLite `:memory:`.

CI runs the same suite on push + PR — see `.github/workflows/tests.yml`.

---

## 8. What could come next

Ed left these on the table (any one of them is a natural next step):

- **Redis wiring in Docker** — swap cache/session/queue drivers for the prod pattern
- **Static analysis (Larastan / PHPStan)** — third CI job catching type errors before ship
- **Backup + restore CLI** — `artisan chrsd:backup` bundling DB + media into an encrypted archive; `artisan chrsd:restore` reversing it
- **Session security hardening** — "log out all other sessions", suspicious-login email alerts
- **Custom Filament theme** — swap the default amber for CHRSD navy `#123420` + gold `#C09020`
- **Per-type certificate templates** — let each `CertificateType` pick a distinct Blade template (Certificate of Employment vs Training vs Service Record with unique layouts)
- **Employment-history reports UI** — surface the auto-captured promotions/transfers on the dashboard, not just in the PDF service record
- **Renewal-request flow** — extend the certificate-request workflow into a one-click "Renew" action fed by the expiry dashboard (auto-populates form from expiring doc)
- **Outbound webhooks** — publish document lifecycle events (issued/revoked/expired) to subscriber URLs for external HRIS / audit systems

Ed's preferred workflow was **one focused deliverable per "step N"**, verified by tests + a smoke script that saves a preview PDF to `%TEMP%\claude\…\scratchpad\`. Continuing that pattern keeps the codebase legible.

---

## 9. Repo hygiene

- **Never commit `.env`** — it's in `.gitignore`.
- Storage symlink (`public/storage → storage/app/public`) is required for Filament media to serve. `start.bat` auto-creates it; on Docker it's auto-created by the entrypoint. Fresh clones need `php artisan storage:link` once.
- Every migration touching a "tenant-owned" table (any table with `organization_id`) must use `$table->foreignId('organization_id')->nullable()->constrained('organizations')->nullOnDelete()`.
- Every new verifiable document type: (a) `use HasVerification` + `verificationPrefix()` on the model, (b) `$table->verificationColumns()` in the migration, (c) register in `VerificationService::$verifiables`. Do all three or serials won't generate.

---

## 10. Contact

If anything's unclear, ask **Ed (`ed@chrsd.org`)** — he built this and has the deepest context.

Welcome aboard.
