# mPDF Migration Guide

## Overview

This CMS has migrated from **Chromium/Browsershot** (a Puppeteer-based headless browser engine) to **mPDF** (a pure PHP library). This enables PDF generation on the shared hosting environment without requiring Node.js or a headless browser process.

**Trade-off**: mPDF supports only **CSS 2.1** (no flexbox, grid, transforms, or CSS 3+ features). All PDF templates must be authored using only CSS 2.1-compliant techniques.

---

## CSS 2.1 Compliance Rules

### ❌ FORBIDDEN (Will not render correctly)

| Feature | Why | Alternative |
|---------|-----|-------------|
| `display: flex` | Flexbox (CSS 3) | Use `float`, `position: absolute`, or `<table>` |
| `display: grid` | CSS Grid (CSS 3) | Use `<table>` elements |
| `position: fixed; inset: 0` | CSS Logical Properties (CSS 4) | Use explicit `top: 0; right: 0; bottom: 0; left: 0` |
| `transform: translate()` | Transforms (CSS 3) | Use `margin-left`/`margin-top` with absolute positioning |
| `object-fit: cover/contain` | CSS 4 | Constrain container size; mPDF will scale image to fit |
| `border-radius` | CSS 3 | Omit; use square corners for print media |
| `box-shadow` | CSS 3 | Omit; print-unfriendly anyway |
| `writing-mode: vertical-rl` | CSS Writing Modes | Use horizontal text or vertical text in a rotated container |
| `background-size: cover/contain` | CSS 3 | Use fixed width/height containers |
| `background-image: url('data:...')` in CSS | Unreliable in mPDF | Use `<img src="data:...">` tags instead |

### ✅ ALLOWED (CSS 2.1 Safe)

- `position: absolute` with explicit `top`, `left`, `right`, `bottom`
- `position: fixed` for page headers/footers
- `float: left/right` for side-by-side layouts
- `width`, `height`, `margin`, `padding`, `border` (all with explicit units)
- `<table>` for complex column layouts
- `<img>` tags with `src="data:image/png;base64,..."` for base64 images
- Inline SVG with `<svg>` elements (basic shapes only; no `<use>` refs)
- Standard font properties: `font-family`, `font-size`, `font-weight`, `color`
- Text alignment: `text-align: center|left|right|justify`
- Page control: `page-break-inside: avoid`, `page-break-after: always`

---

## Asset Handling

### Image Path Rules

**CRITICAL**: All images must arrive as **base64 data URIs** or **absolute filesystem paths**. Do NOT use:
- Relative URLs: `images/logo.png` ❌
- Web URLs: `https://example.com/logo.png` ❌
- public_path() as a URL: `{{ public_path('images/logo.png') }}` ❌

### Correct Approaches

#### 1. Data URI (Recommended for brand assets)

```blade
<!-- Service converts file to data URI before passing to view -->
<img src="{{ $logoUrl }}" alt="Logo">
```

In the service:
```php
$logoUrl = 'data:image/png;base64,' . base64_encode(file_get_contents(
    public_path('images/brand/logo.png')
));
```

#### 2. Absolute Filesystem Path (For mPDF direct access)

mPDF can read local files directly if given an absolute path. This is used for large assets:
```php
// In CSS @page rule or body styling:
// mPDF can resolve absolute paths like /var/www/html/public/...
```

**Best practice**: Always use data URIs—they're self-contained and work reliably across environments.

---

## Service-side Refactoring

### Pattern: Convert Assets to Data URIs

All PDF generator services (CertificateGeneratorService, IdCardGeneratorService, LetterGeneratorService) must:

1. **Read files** from the filesystem using `public_path()`
2. **Convert to data URIs** before passing to Blade templates
3. **Pass as template variables** (not rely on public_path() in the view)

Example:
```php
protected function fileToDataUri(?string $path): ?string {
    if (!$path || !is_file($path)) return null;
    $mime = mime_content_type($path) ?: 'image/png';
    return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
}

// In generate() method:
$pdfBytes = $this->pdf->render($html, [
    'logoUrl' => $this->fileToDataUri(public_path('images/brand/logo.png')),
    // ...
]);
```

---

## Blade Template Patterns

### Layout: Side-by-Side with Float

```blade
<style>
  .container { width: 100%; overflow: hidden; }
  .left { float: left; width: 48%; }
  .right { float: right; width: 48%; }
</style>

<div class="container">
  <div class="left">Left content</div>
  <div class="right">Right content</div>
</div>
```

### Layout: Table Columns

```blade
<table style="width: 100%; border-collapse: collapse;">
  <tr>
    <td style="width: 33%; padding: 2mm;">Col 1</td>
    <td style="width: 33%; padding: 2mm;">Col 2</td>
    <td style="width: 33%; padding: 2mm;">Col 3</td>
  </tr>
</table>
```

### Layout: Absolute Positioning (Fixed Page Elements)

```blade
<style>
  @page { margin-top: 100mm; margin-bottom: 26mm; }
  .letterhead {
    position: fixed;
    top: -100mm;    /* Negative margin to reach physical edge */
    left: 0;
    width: 210mm;
    height: 100mm;
  }
</style>

<div class="letterhead">
  <img src="{{ $letterheadUri }}" alt="Letterhead">
</div>
```

### Vertical Text (Without CSS Writing Modes)

```blade
<style>
  .sidebar { position: absolute; top: 0; left: 0; width: 10mm; height: 54mm; }
  .text { position: absolute; top: 50%; left: 50%; width: 8mm; height: 30mm;
          margin-top: -15mm; margin-left: -4mm; text-align: center;
          word-break: break-all; line-height: 1.8; }
</style>

<div class="sidebar">
  <span class="text">VERTICAL TEXT</span>
</div>
```

---

## Page Structure Best Practices

### Print Margins and Headers/Footers

mPDF's `@page` rule sets physical margins. Use `position: fixed` with negative offsets to place content in those margins:

```blade
<style>
  @page {
    size: A4 portrait;
    margin-top: 100mm;      /* Safe text starts 100mm down */
    margin-bottom: 25mm;    /* 25mm footer zone */
    margin-left: 20mm;
    margin-right: 15mm;
  }

  .letterhead-strip {
    position: fixed;
    top: -100mm;            /* Push to physical edge */
    left: 0;
    width: 210mm;           /* Full page width (A4) */
    height: 100mm;
  }

  .footer-strip {
    position: fixed;
    bottom: -25mm;
    left: 0;
    width: 210mm;
    height: 25mm;
  }

  .content { position: relative; z-index: 2; }
</style>
```

### Page Break Control

```blade
<style>
  .section { page-break-inside: avoid; }   <!-- Keep section on one page -->
  .heading { page-break-after: avoid; }    <!-- Heading + 1st para on same page -->
</style>
```

---

## Common Gotchas

### Issue: Image Not Showing

**Cause**: Image URL is relative or a public_path() string.
```blade
<!-- ❌ WRONG -->
<img src="{{ public_path('images/logo.png') }}">
```

**Fix**: Convert to data URI in the service.
```blade
<!-- ✅ RIGHT -->
<img src="{{ $logoUrl }}">  <!-- $logoUrl is data:image/... -->
```

### Issue: Absolute Positioning Overlaps

**Cause**: Forgot `z-index` layering or nested elements compete for space.
```blade
<style>
  .bg { position: fixed; top: 0; left: 0; z-index: 0; }
  .content { position: relative; z-index: 2; }
</style>
```

### Issue: Table Columns Don't Align

**Cause**: Forgot `table-layout: fixed` or no explicit column widths.
```blade
<table style="width: 100%; table-layout: fixed;">
  <tr>
    <td style="width: 40%;"> ... </td>
    <td style="width: 60%;"> ... </td>
  </tr>
</table>
```

### Issue: Text Not Wrapping Correctly

**Cause**: No `word-wrap: break-word` or container too narrow.
```blade
<div style="width: 60mm; word-wrap: break-word; overflow: hidden;">
  Very long text that needs wrapping
</div>
```

---

## Testing & Validation

### Step 1: Check CSS Syntax

Use a CSS 2.1 validator:
```bash
# Search each template for forbidden properties
grep -E "flex|grid|transform|object-fit|border-radius|box-shadow|writing-mode" \
  resources/views/documents/**/*.blade.php
```

### Step 2: Test Rendering

1. Generate a PDF from the admin UI
2. Open the PDF and verify:
   - ✅ Layout renders without overlaps
   - ✅ Images appear (not broken)
   - ✅ Text wraps correctly
   - ✅ Page breaks are clean
   - ✅ Colors print accurately

### Step 3: Regression Check

Test all PDF types after changes:
- [ ] Letters (with letterhead)
- [ ] Certificates (with seals/logos)
- [ ] ID cards (front/back/combined)
- [ ] Reports (tables, multi-page)

---

## Troubleshooting

### PDF Renders Blank or Corrupted

Check the MpdfPdfService temp directory:
```php
// storage/app/mpdf/ should exist and be writable
if (!is_dir(storage_path('app/mpdf'))) {
    mkdir(storage_path('app/mpdf'), 0775, true);
}
```

### Images Fail to Render

Verify data URIs are valid:
```php
// Test in tinker:
$uri = 'data:image/png;base64,' . base64_encode(file_get_contents(public_path('images/logo.png')));
echo strlen($uri);  // Should be > 1000
```

### Performance Issues

mPDF caches fonts in `storage/app/mpdf/`. Clear if needed:
```bash
rm -rf storage/app/mpdf/*
```

---

## Migration Checklist

- [ ] All image assets converted to data URIs in services
- [ ] All Blade templates checked for forbidden CSS properties
- [ ] Tested all PDF types (letters, certs, ID cards, reports)
- [ ] Verified asset paths use data URIs or absolute paths (no relative URLs)
- [ ] No `public_path()` calls in inline HTML `src` or CSS `url()`
- [ ] Page margins align with fixed-position letterheads
- [ ] Print-critical colors defined (avoid relying on `@media screen`)

---

## References

- **mPDF CSS Support**: https://mpdf.github.io/html-css/supported-css.html
- **CSS 2.1 Spec**: https://www.w3.org/TR/CSS2/
- **Print Media Guide**: https://developer.mozilla.org/en-US/docs/Web/CSS/Media_Queries/Using_media_queries#print
