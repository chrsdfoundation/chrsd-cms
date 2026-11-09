# Executive business card: deployment notes

## Requirements on the host

| Requirement | Used for | Check on the host |
|---|---|---|
| PHP 8.2+ (`composer.json` requires `^8.2`) | everything | `php -v` |
| `zlib` | compressing PDF streams (`gzcompress`) and reading them back (`gzuncompress`) | `php -m \| grep zlib` |
| `mbstring` | font code points (`mb_ord`), font name decoding | `php -m \| grep mbstring` |
| `pcre` | parsing the PDF and vCard (always on) | `php -m \| grep pcre` |

Not needed at runtime: `gd` (only the dev-time decoder test uses it), `imagick`, `curl`,
`intl`, Node, Chromium, Ghostscript, a queue worker, `exec`/`shell_exec`/`proc_open`.

Local check, with the same flags the host uses:

```bash
php -d memory_limit=128M -d max_execution_time=30 -r "echo 'zlib=', extension_loaded('zlib') ? 'yes' : 'NO', ' mbstring=', extension_loaded('mbstring') ? 'yes' : 'NO', PHP_EOL;"
```

## Directories and permissions

| Path | Purpose | Permission |
|---|---|---|
| `storage/app/private/business-cards/` | exported PDFs (personal contact data) | writable by the web user; created on first export |
| `storage/framework/` | cache and views | writable (already required by Laravel) |
| `resources/fonts/business-card/` | embedded fonts and OFL texts | read-only is fine |
| `resources/business-card/geometry.json` | converted logo and icon geometry | read-only is fine |
| `public/fonts/business-card/` | fonts for the on-screen preview only | read-only, served by the web server |

No writes go to `/tmp` or any system temp directory. The PDF writer works in memory.

PDFs are private. Do not add `storage:link` for them. The download route is
`/business-cards/exports/{export}`, behind `auth` and the card's view permission.

## Install and migrate

```bash
composer install --no-dev --optimize-autoloader --no-interaction --no-scripts
php artisan migrate --force
```

`--no-scripts` avoids the Filament upgrade hook on a locked host. Run
`php artisan filament:upgrade` if the admin assets need it.

The migrations are additive:

- `2026_11_09_100001_add_executive_card_fields_to_business_cards`: new columns, and a
  backfill from the old columns for every existing card. The backfill copies the old
  values as they are. It does not check them, so review each card's phones after the
  migration. It also copies a fault that exists today: card 1's office phone holds the
  WhatsApp number. The consistency check blocks approval until it is corrected.
- `2026_11_09_100002_create_business_card_exports_table`: the audit table.

Permission: the seeder adds `approve_business_card`. Grant it to the roles that may
approve. super_admin already has it through Gate.

## Configuration

Settings are in `config/business_card.php`. Values marked UNVERIFIED are the brief's
assumptions and need the printer's answer before production use:

- `foil_min_line_pt` (0.75): the printer's real minimum is unknown.
- `qr_min_module_mm` (0.33): below this an export needs a recorded acknowledgement.
- `max_total_ink_percent` (300): the usual convention; confirm with the printer.
- `bleed_in` (0.125): the spec's bleed. Confirm the convention with the printer.

## Performance on a constrained setup

Measured on the development machine, PHP 8.3.31, with `-d memory_limit=128M -d max_execution_time=30`:

| Step | Result |
|---|---|
| Layout, QR, artwork and foil plate for one card | 0.24 s |
| Artwork PDF | 160 KB |
| Foil plate PDF | 53 KB |
| Peak memory, including the Laravel bootstrap | about 50 MB |

No caching is needed at this cost, so none was added.

## Smoke test on the host

1. Open a card in the admin and confirm the preview shows both sides.
2. Use "Artwork PDF" and "Foil plate PDF" on one card. Each should download as a PDF.
3. Open the artwork in a PDF viewer. It should show 2 pages and no RGB colour.
4. Confirm a row appears in `business_card_exports` for each download.
5. Approve the card with a user who holds `approve_business_card`, then mark it sent to press.

## Rollback

1. Stop using the approval and export actions (or revert the deployed commit).
2. Roll back the migrations. This drops the exports table and the new columns:

   ```bash
   php artisan migrate:rollback --step=2 --force
   ```

   Exported files stay on the private disk and can be deleted by hand. The old columns
   were never removed, so the legacy print page keeps working.
3. Redeploy the previous commit.

## Known gaps before production

- Bengali name fields are not shipped (no shaping engine).
- Kerning and ligatures are not applied (see `ENGINE_DECISION.md`).
- The CMYK values need a physical proof on the press.
- The printer's foil minimum and bleed convention need the printer's confirmation.
