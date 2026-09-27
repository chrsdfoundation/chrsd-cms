# CHRSD CMS

Internal Laravel 12 + Filament 3 back-office for the **Centre for Humanitarian
Research and Social Development Foundation** (CHRSD). Issues and manages the
foundation's **verifiable documents** — certificates, ID cards, money receipts,
and official letters — each carrying an HMAC-signed hash, a per-year serial, and
a QR code that resolves to the public verification page on the CHRSD Website.

## What's inside
- **Filament 3.2 admin** with role/permission control (Filament Shield +
  Spatie Permission) and full activity logging (Spatie Activitylog).
- **Verifiable-document backbone** — HMAC-SHA256 integrity hashes, per-year
  serial numbers, QR/verify links built against `VERIFY_BASE_URL` (the public
  Website app).
- **PDF generation** — pure-PHP mPDF via `MpdfPdfService` for certificates,
  ID cards, reports, and approved letters (no Chromium/Node needed, runs on
  shared cPanel hosting). Barcodes via `milon/barcode`.
- **Media** — Spatie Media Library for uploaded assets and signatures.

## Tech stack
- **PHP 8.3**, **Laravel 12**, **Filament 3.2**
- **SQLite** (dev) / **MySQL 8** (prod) — toggle via `.env`
- **Tailwind 3.4** + **Vite 6**
- Spatie Permission / Media Library / Activitylog, Filament Shield, milon/barcode,
  mPDF

## Quick start
```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve
```
On Windows, `start.bat` wraps the common launch steps. See
[ONBOARDING.md](ONBOARDING.md) for the full environment guide (PHP extension
flags, DB toggle, admin login, verify endpoint).

## Documentation
- [ONBOARDING.md](ONBOARDING.md) — environment setup, DB toggle, admin login,
  verify endpoint, PDF engine notes.

## License
Proprietary — property of CHRSD Foundation. See [LICENSE](LICENSE).
