# mPDF PDF Generation Refactoring — Complete Summary

## Completion Date
2026-07-28

## Objective
Migrate the CHRSD CMS PDF generation system from Chromium/Browsershot (modern CSS) to mPDF (CSS 2.1 strict) to enable PDF rendering on shared hosting without Node.js or headless browser processes.

---

## Files Modified

### Service Layer (PHP)

#### 1. **app/Services/Documents/MpdfPdfService.php** ✅
- Added comprehensive docstring warning about CSS 2.1 constraints
- Added `disable_html_object_protocol` config for security
- Added CSS 2.1 validation comment
- No functional changes; service already correct

#### 2. **app/Services/Documents/LetterGeneratorService.php** ✅
- **renderPdf()**: Updated to pass `letterhead_uri` data URI to template
- **dataUriFor()**: Enhanced MIME type detection (was hardcoded to image/png)
- **buildContext()**: Added `letterhead_uri` to context array
- All image assets now converted to data URIs before rendering

#### 3. **app/Services/Documents/CertificateGeneratorService.php** ✅
- Updated to convert ALL image assets to data URIs:
  - Signature images (sig1, sig2) → data URIs
  - Brand assets (logoUrl, sealUrl, watermarkUrl) → data URIs
  - Removed comments about "Chromium" rendering
- Helper methods `fileToDataUri()` and `brandSignatureDataUri()` already present and utilized

#### 4. **app/Services/Documents/IdCardGeneratorService.php** ✅
- Updated comment from "Puppeteer" to "mPDF"
- Already using `toDataUri()` for all image assets (no changes needed functionally)
- Confirmed data URI pipeline is correct

#### 5. **app/Services/Reports/ReportService.php** ✅
- Already using MpdfPdfService
- Reports use table-based layouts (CSS 2.1 compliant)
- No changes required

#### 6. **app/Services/Documents/TemplateRenderer.php** ✅
- Added `letterhead_uri` to template context passed to shell overrides
- Allows database-driven templates to use letterhead images

### View Layer (Blade Templates)

#### 7. **resources/views/documents/letters/default.blade.php** ✅
- Removed `public_path()` from image src attribute
- Updated to use `$letterhead_uri` parameter
- Changed `.signature .name` from `display: inline-block` to `display: block` (CSS 2.1)
- Added `.signature .title` explicit `display: block` rule

#### 8. **resources/views/documents/certificates/default.blade.php** ✅
- Converted `background-image: url()` to `<img>` tags for logo and seal
- Removed `background-size: contain` CSS 3 property
- HTML structure now uses `<img src="{{ $logoUrl }}">` instead of CSS backgrounds
- All CSS 2.1 compliant

#### 9. **resources/views/documents/id_cards/default-front.blade.php** ✅
CSS 2.1 refactoring:
- Fixed `inset: 0` → explicit `top: 0; right: 0; bottom: 0; left: 0`
- Removed flexbox sidebar layout → absolute positioning
- Removed `border-radius: 1.5mm` from photo
- Removed `object-fit: cover/contain` from images
- Converted vertical text from `writing-mode: vertical-rl; transform: rotate()` to absolute positioning

#### 10. **resources/views/documents/id_cards/default-back.blade.php** ✅
CSS 2.1 refactoring (same as default-front):
- Fixed `inset: 0` → explicit positioning
- Removed flexbox → absolute positioning
- Removed `transform: translate(-50%, -50%)` → use `margin-left` and `margin-top`
- Removed `border-radius`
- Removed `object-fit`

#### 11. **resources/views/documents/id_cards/combined.blade.php** ✅
CSS 2.1 refactoring:
- Removed fallback `public_path()` assignments (service always passes data URIs now)
- Fixed `inset: 0` → explicit positioning
- Changed photo from `background-image` to `<img>` tag
- Removed `background-size: cover`
- Removed `border-radius: 1mm` from QR box
- Changed `.contact .icon` from `display: inline-block` to `display: block`
- Removed `box-shadow` from `.card`

#### 12. **resources/views/documents/templates/letter-shell.blade.php** ✅
- Updated letterhead to use `$letterhead_uri` parameter instead of `public_path()`
- Added conditional check for letterhead_uri existence

### Documentation

#### 13. **docs/MPDF_MIGRATION_GUIDE.md** ✅ (NEW)
Comprehensive guide covering:
- CSS 2.1 compliance rules with forbidden/allowed features
- Asset handling requirements (data URIs vs filesystem paths)
- Service-side refactoring patterns
- Blade template layout patterns (float, table, absolute positioning)
- Common gotchas and troubleshooting
- Migration checklist

#### 14. **docs/MPDF_REFACTORING_SUMMARY.md** ✅ (NEW)
This file — complete audit trail of all changes.

---

## CSS 2.1 Compliance Changes

### Removed (Not CSS 2.1 Compliant)

| Property | Occurrences | Reason |
|----------|-------------|--------|
| `display: flex` | 2 (ID cards) | Flexbox (CSS 3) |
| `display: grid` | 0 | Grid (CSS 3) — not found |
| `object-fit: cover/contain` | 3 (certificates, ID cards) | CSS 4 |
| `border-radius` | 3 (ID cards, combined) | CSS 3 |
| `transform: translate()/rotate()` | 2 (ID cards) | CSS 3 Transforms |
| `writing-mode: vertical-rl` | 2 (ID cards) | CSS Writing Modes |
| `inset: 0` | 3 (ID cards, combined) | CSS Logical Properties (CSS 4) |
| `box-shadow` | 1 (combined) | CSS 3 |
| `background-size: cover` | 1 (combined) | CSS 3 |
| `background-image: url()` for logos | 2 (certificates) | Unreliable in mPDF; use `<img>` |

### Replaced With (CSS 2.1 Equivalents)

| Old | New | Benefit |
|-----|-----|---------|
| `display: flex; align-items: center` | `position: absolute; top: 50%; margin-top: -Xmm` | Explicit positioning, reliable in mPDF |
| `object-fit: contain` | Constrain container size only | mPDF scales image to fit container |
| `border-radius` | Removed (square corners) | Print-appropriate |
| `transform: translate(-50%, -50%)` | `margin-left`/`margin-top` with negatives | Absolute positioning |
| `inset: 0` | `top: 0; right: 0; bottom: 0; left: 0` | Explicit CSS 2.1 |
| `background-image: url()` for static assets | `<img src="data:...">` tags | Self-contained, mPDF-reliable |

---

## Asset Pipeline Refactoring

### Data URI Conversion Pattern

All services now follow this pattern:

```php
// 1. Service reads file from filesystem
$logoPath = public_path('images/brand/logo.png');

// 2. Converts to data URI
$logoUri = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));

// 3. Passes to template
$html = view('documents.letters.default', [
    'logoUrl' => $logoUri,
])->render();

// 4. mPDF renders the HTML; data URI is self-contained
$pdf = $this->pdf->render($html, [...]);
```

### Critical Service Methods

| Service | Method | Purpose |
|---------|--------|---------|
| LetterGeneratorService | `dataUriFor()` | Convert file to data URI |
| CertificateGeneratorService | `fileToDataUri()` | Convert file to data URI |
| CertificateGeneratorService | `brandSignatureDataUri()` | Brand-kit signature fallback |
| IdCardGeneratorService | `toDataUri()` | Convert file to data URI |

All three methods now detect MIME type correctly (not hardcoded).

---

## Template Variable Changes

### Added Template Variables

| Service | Variable | Used In | Purpose |
|---------|----------|---------|---------|
| LetterGeneratorService | `letterhead_uri` | letter/default, letter-shell | Letterhead background image |
| CertificateGeneratorService | `logoUrl` | certificate/default | Brand logo (now data URI) |
| CertificateGeneratorService | `sealUrl` | certificate/default | Organizational seal (now data URI) |
| IdCardGeneratorService | `logoUrl`, `roundLogoUrl` | ID card templates | Brand logos (confirmed data URI) |
| TemplateRenderer | `letterhead_uri` | letter-shell | DB-template letterhead |

---

## Testing Checklist

### Unit Tests (Run First)
- [ ] LetterGeneratorService::renderPdf() passes letterhead_uri
- [ ] CertificateGeneratorService::generate() converts all images to data URIs
- [ ] IdCardGeneratorService::generate() passes data URIs for all images
- [ ] TemplateRenderer passes letterhead_uri to shell templates

### Integration Tests (Run Second)
- [ ] Generate a letter PDF → letterhead appears on every page
- [ ] Generate a certificate PDF → logo, seal, signatures render
- [ ] Generate ID card PDFs → photo, logos, QR all visible
- [ ] Generate reports → tables layout correctly

### Visual Tests (Run Last)
- [ ] Open each PDF in a PDF viewer
- [ ] Verify images are not broken/missing
- [ ] Check page breaks are clean
- [ ] Confirm no overlapping elements
- [ ] Validate text wrapping

---

## Known Limitations (mPDF vs Chromium)

1. **No rounded corners** — mPDF doesn't support `border-radius`, so all boxes are square
2. **No vertical text rotation** — use absolute positioning workaround instead of CSS transforms
3. **No flexbox/grid layouts** — use `<table>` or floats instead
4. **Limited SVG support** — basic shapes OK, no embedded images via `<use>` references
5. **No CSS transitions/animations** — not applicable for static PDFs
6. **Absolute positioning is pixel-precise** — use explicit `top`, `left`, `right`, `bottom` values

---

## Environment Notes

### Deployment Requirements
- mPDF needs `storage/app/mpdf/` directory (writable)
- PHP GD extension NOT required (unlike DomPDF)
- File permissions: `storage/app/mpdf` should be `775`

### Performance
- mPDF is pure PHP → slower than Chromium for large volumes
- Caches fonts in `storage/app/mpdf/` → clear if fonts don't update
- Memory: Typical PDF ~5-10MB; mPDF loads into RAM

---

## Next Steps

1. **Commit changes** to branch `feat/migrate-pdf-to-mpdf`
2. **Run full test suite** to ensure no regressions
3. **Manual QA** on all PDF types:
   - Generate 5 letters (test page breaks)
   - Generate 5 certificates (test layout variation)
   - Generate 5 ID cards (test front/back/combined)
   - Generate 1 of each report type
4. **Deploy to staging** and verify with real data
5. **Merge PR** once all tests pass

---

## Rollback Plan

If critical issues arise:

```bash
git revert <merge-commit>  # Revert to Browsershot
# Restore old services from commit history
git checkout HEAD~N -- app/Services/Documents/
git checkout HEAD~N -- resources/views/documents/
```

The old Browsershot pipeline is still in `git history` and can be restored if needed.

---

## References

- **Migration Guide**: `docs/MPDF_MIGRATION_GUIDE.md`
- **mPDF Docs**: https://mpdf.github.io/
- **CSS 2.1 Spec**: https://www.w3.org/TR/CSS2/
- **Git Branch**: `feat/migrate-pdf-to-mpdf`
