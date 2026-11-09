# Business card PDF engine: decision and evidence

Status: decided (custom pure-PHP writer), with the gaps listed below.

## Decision

Use a custom, pure-PHP PDF writer (`app/Services/BusinessCard/Pdf/`) behind the
`CardPdfRenderer` interface. Draw the layout engine's primitives through it. No
PDF library is added.

Chosen by the owner after the Phase 1 report. The options were: keep browser
print, add a server engine alongside it, or write a custom writer. The custom writer
was chosen. It was not compared against TCPDF, FPDI or mPDF, because the owner
chose the custom writer. The capability matrix below is for the custom writer only.

Why not the other paths in this repo:

- mPDF was removed by commit `75a71e0` and is not in `composer.json`, `composer.lock`
  or `vendor/`. `app/Services/Documents/MpdfPdfService.php` is dead code.
- The brief's runtime limits (no exec, no Chromium, no Node, no Imagick, no queue)
  rule out Browsershot and Ghostscript-based flows.

## Capability matrix

Evidence key: **VERIFIED (test)** = a PHPUnit test asserts it. **VERIFIED (render)** =
the output was rendered with pdf.js and inspected. **VERIFIED (parse)** = fontTools or
the PHP preflight parsed the file. **NOT MET** = not implemented, with the fallback.

| # | Capability | Priority | Status | Evidence / fallback |
|---|---|---|---|---|
| 1 | Pure PHP inside shared-hosting limits | MUST | VERIFIED | No exec, Chromium, Node or Imagick calls (grep of the card code). Runtime uses zlib and mbstring only. Both PDFs for one card: 0.24 s, peak about 50 MB including the Laravel bootstrap, under `memory_limit=128M` and `max_execution_time=30`. |
| 2 | DeviceCMYK, no RGB operators | MUST | VERIFIED (test, parse) | `PdfPreflight` counts `rg`/`RG` in every decoded content stream. Artwork and plate report 0. `PdfWriterTest::test_preflight_flags_rgb_operators` checks the detector. |
| 3 | Named spot `Silver_Foil` (Separation, CMYK alternate) | MUST | VERIFIED (test, render) | Artwork preflight lists `Silver_Foil` and `All`. The separation's alternate is the spec's `alt_cmyk_for_screen`. Rendered foil shows as grey on screen. |
| 4 | TrimBox, BleedBox, MediaBox | MUST | VERIFIED (test) | `PdfWriterTest` asserts MediaBox 306×198, TrimBox 27,27,279,171, BleedBox 18,18,288,180. The preflight requires all three on every page. |
| 5 | Crop marks in registration colour | MUST | VERIFIED (test, render) | Marks use the `All` separation, 0.25 pt, from the bleed edge, 13.5 pt long. Plate and artwork both carry them. |
| 6 | Embedded subset TrueType (no substitution) | MUST | VERIFIED (parse, test) | CIDFontType2 with FontFile2. The subset is rebuilt with only the used glyphs and composite components. fontTools reads it back (36 glyphs for a short string). `PdfWriterTest::test_subset_font...`. |
| 7 | Vector paths, non-zero fill, clipping | MUST | VERIFIED (render) | Logo, icons, QR (merged runs) and the bleed clip render correctly in pdf.js. |
| 8 | Linear gradients in CMYK (back emblem) | SHOULD | VERIFIED (render, test) | Axial shading, type 2, DeviceCMYK, stops from the spec's table, through a type 3 stitching function. `PdfWriterTest::test_shading...`. |
| 9 | Overprint on fills | SHOULD | VERIFIED (test) | ExtGState `/OP true /op true /OPM 1`, applied per primitive. The plate uses no overprint, as in the reference. |
| 10 | Kerning and ligatures | SHOULD | **NOT MET** | The fonts store kerning in GPOS, which the writer does not read. Fallback: no kerning and no ligatures. Glyph spacing is slightly looser than the reference in long lines. Listed under open items. |
| 11 | Bengali shaping (`*_bn` fields) | COULD | **NOT MET (left out)** | No shaping engine. The `*_bn` fields are not added. |
| 12 | PDF/X output | COULD | **NOT CLAIMED** | No preflight tool was run. The output is not claimed as PDF/X. |

## Visual comparison with the reference artwork

Method: render `CHRSD_Card_Print_Artwork_Silver.pdf` and the export with the same
pdf.js 3.11.174 viewer at scale 1.6, and compare by eye side by side. Result:
layout, text positions, icons, hairlines, divider, QR panel and caption match.

Differences, each with its reason:

- **QR code.** The reference uses the client's 81-module code. The export uses the
  compact vCard, which is 61 modules for this contact. Module size is 0.35 mm
  (spec reference 0.35 mm).
- **Text is embedded, not outlined.** The reference outlines glyphs. The export embeds
  subset fonts, so text stays selectable and the file is smaller.
- **No kerning or ligatures** (matrix row 10).
- **Slug label.** Same text and size. Placed at 8.76 pt above the page bottom, which
  may differ by a fraction of a point from the reference.
- **Reference helper font.** The reference strips reportlab's unused Helvetica. The
  export embeds only the three fonts used.

## Preflight (every export)

`PdfPreflight` checks the output structurally, in PHP:

- The xref offsets point at the object definitions, and the trailer is present.
- Every page has MediaBox, TrimBox and BleedBox.
- No RGB operators and no DeviceRGB in resources.
- The required separations exist: `Silver_Foil` and `All` for artwork, `All` for the plate.
- Every font descriptor has FontFile2.

It does not claim PDF/X. A real preflight tool would be needed for that.

## Known limits

- Fonts are static Montserrat (Regular, SemiBold) and Cormorant Garamond (SemiBold),
  from the user's machine, under SIL OFL 1.1. The OFL texts are in
  `resources/fonts/business-card/`. Variable fonts are rejected by the parser.
- The CMYK values are estimates from the spec. They need a physical proof.
- The printer's foil minimum line weight is unknown. It is set to 0.75 pt in
  `config/business_card.php` as an assumption.
