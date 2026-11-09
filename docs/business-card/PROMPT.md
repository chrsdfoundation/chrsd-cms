# MASTER PROMPT: Implement the CHRSD executive business card in the CHRSD-CMS card builder

You are a principal engineer who is fluent in Laravel 12, Filament 3, PHP PDF generation and professional print production (CMYK, bleed, trim, spot colours, hot-foil). You are working inside an EXISTING production-bound codebase. Your job is to extend it, not to rewrite it.

Read this whole file, then `card-spec.json`, then open every file in `reference/` before you write any code.

---

## 0. Rules of engagement (non-negotiable)

1. **Truth over speed.** Every claim about a library, a PDF feature or this codebase must be VERIFIED by reading vendor source, official docs, or running code in this repo. Never invent a method name, config key, package or CLI flag. In your final report, label each capability `VERIFIED (evidence)` or `ASSUMED`. If something cannot be done, say so plainly and propose options. Do not paper over gaps.
2. **Discover before you build.** You have not seen this repo. Do Phase 1 and report findings before creating files. Do not assume names of models, resources, panels, disks or roles.
3. **Match existing conventions** (namespaces, folder layout, naming, Filament panel structure, policies, test style, lint config). Reuse existing PDF, QR, settings, media and permission infrastructure if it exists.
4. **Safety.** Work on a new branch (`feature/business-card-print`). Never touch `.env`, production data, or run destructive migrations. New migrations must be additive and reversible. No secrets in code.
5. **Hosting constraint is a hard design input.** Production is cPanel shared hosting with LVE process limits. At runtime there must be NO Chromium/Browsershot, NO Node, NO `exec`/`shell_exec`/`proc_open`, NO Imagick/Ghostscript assumptions, NO long-running queue workers. Everything runs synchronously in pure PHP within modest memory and time limits. Dev-only tools (fonttools, pdfinfo, qpdf) may be used on a developer machine and in tests, never on the server.
6. **No scope creep.** Implement what is in section 3. Put ideas in the final report under "Suggestions", not in the diff.
7. **Small, reviewable commits** with clear messages, and tests with each.

---

## 1. Phase 1: Discovery (report back before building)

Inspect the repo and produce a short written report covering:

- PHP, Laravel, Filament and Livewire versions. Installed PDF, QR and barcode packages and their versions (`composer.json`, `composer.lock`).
- Whether a business-card, ID-card, certificate, letter or receipt feature already exists. List its models, migrations, Filament resources/pages, services, Blade views, routes, policies, jobs, config files and tests. Describe what it does today.
- How PDFs are currently generated, and whether the Browsershot/Chromium problem has already been removed. Which PDF engine is wired in (mPDF is the intended pure-PHP direction), its temp/font directories, and how fonts are registered.
- The auth and permission model (roles, policies, any Shield-style package) and which Filament panel(s) exist.
- Storage disks in use, and which are public vs private.
- Test framework (Pest or PHPUnit), CI config, code style tools.
- PHP extensions available locally (`gd`, `mbstring`, `zlib`, `intl`, `bcmath`) vs what production likely has. Flag any extension you need that might be missing on cPanel.

If an existing business-card feature conflicts with this brief, STOP and ask, listing the options.

---

## 2. Reference artefacts (ground truth)

Copy this bundle into the repo at `docs/business-card/` (keep names). Treat these as the source of truth for the design:

| File | What it is |
|---|---|
| `card-spec.json` | The design as data: page geometry, colours, fonts, every element's coordinates and print constants. Authoritative for layout. |
| `reference/preview-executive-card.html` | Approved on-screen preview (front and back, print guides toggle, foil/flat toggle). Visual ground truth. Do NOT ship its CDN Tailwind or Google Fonts. |
| `reference/CHRSD_Card_Print_Artwork_Silver.pdf` | Approved print PDF: CMYK, 3.5 x 2 in trim, 0.125 in bleed, crop marks, `Silver_Foil` spot colour, vector QR. Your PDF output must match this visually. |
| `reference/CHRSD_Card_Silver_Foil_Plate.pdf` | Foil-only plate (black = foil), both sides. |
| `reference/reference_build_pdf_reportlab.py` | Python reference implementation that produced the PDFs. Read it for exact geometry, baseline maths, overprint and spot-colour usage. It is a reference, not something to port line by line. |
| `reference/reference_build_preview.py` | Generator of the HTML preview. |
| `assets/chrsd-logo.svg` | Official emblem (31 gradient paths, viewBox 320 x 290.25). |
| `assets/qr-sample-vcard-81modules.svg` | Example QR from the client's generator, for comparison only. |
| `tools/make_static_fonts.py` | Dev-only script that makes static TTFs from the variable Google fonts. |

Confirmed facts about the reference output (inspect to re-verify): CMYK only (no RGB operators), two named colourants (`Silver_Foil` spot and `All` for crop marks), fonts outlined, TrimBox (27,27,279,171) pt, BleedBox (18,18,288,180) pt, page 306 x 198 pt.

**Unverified assumptions you must expose as config, not hard-code:** the 0.125 in bleed and 3.5 x 2 in size suit the client's printer in Bangladesh; the CMYK recipes are estimates that need a physical proof; the printer's real foil minimum line weight is unknown (spec uses 0.75 pt); maximum total ink 300%.

---

## 3. What to build

### 3.1 Single source of truth for layout

Build a **layout engine** that turns card data into a flat list of drawing primitives (rect, path, text with font/size/tracking/baseline, logo, icon, QR matrix, each with colour model and overprint flag). BOTH the Filament live preview and the PDF renderer consume that same list. Do not convert HTML to PDF. Draw primitives directly through the PDF engine's API so preview and print cannot drift apart.

The layout engine must:
- Load geometry from `card-spec.json` (versioned; store the spec version on each export).
- Be data-driven: 1 to 8 contact rows in groups (phones, emails, web) with a 16 px group gap, 1 to 3 address lines. Reflow row tops from the spec rules.
- Measure text with the SAME engine and fonts used for output, so validation matches reality.
- Validate and return structured errors/warnings (never silently truncate): text outside the safe area (45 px = 0.15 in inside trim), overlaps, font size below 6 pt, foil line below the configured minimum, QR module smaller than the configured minimum.

### 3.2 Data model (additive migrations; adapt names to repo conventions)

`business_cards`: id, optional staff/user relation, `person_name`, `person_title`, optional `person_name_bn` and `person_title_bn`, `org_name` (default from settings), `phones` JSON (`type` office|mobile|whatsapp, `number_e164`, `display`), `emails` JSON (`type`, `address`), `website`, `linkedin_handle`, `address_lines` JSON (max 3), `qr_profile` (compact|full), `template_key`, `template_version`, `status` (draft|in_review|approved|sent_to_press), approver and approved_at, notes, timestamps, soft deletes.

`business_card_exports` (audit): card id, kind (artwork|foil_plate), file path, sha256, engine name and version, spec version, QR profile, preflight report JSON, created_by, created_at. Store files on a PRIVATE disk. They contain personal contact data.

### 3.3 Input handling services

- **Phone**: normalise to E.164 and a display form with a real library (evaluate `giggsey/libphonenumber-for-php`; verify before adding). Validate Bangladesh numbers (+880). Preserve the client's display style (`+880 1602-778984`) as an explicit display value.
- **LinkedIn**: accept a handle or any URL form. Normalise to exactly `https://www.linkedin.com/in/<handle>`. Reject or repair doubled paths such as `linkedin.com/in/www.linkedin.com/in/x` (this exact bug shipped on the old card). Unit-test it.
- **vCard builder** (vCard 3.0 per RFC 2426; escape `, ; \` and newlines, CRLF line endings): office phone `TEL;TYPE=WORK,VOICE`, mobile `TEL;TYPE=CELL`, WhatsApp as `https://wa.me/<digits>`, work emails typed `WORK` (the old card wrongly tagged one `HOME`).
- **Consistency checker**: parse the generated vCard back and compare against the fields printed on the card (every printed phone, email, URL). Any mismatch blocks approval with a specific message. The old QR contained the WhatsApp number as the work phone and omitted the printed office line. This check exists to make that impossible.

### 3.4 QR

- Generate the QR **matrix** server-side in pure PHP (reuse an installed QR package if present; otherwise evaluate `bacon/bacon-qr-code` or `endroid/qr-code` and verify it exposes the module matrix). ECC level M.
- Draw it as **vector rectangles** (merge horizontal runs into one path), single plate K100 with overprint, on a paper-white knock-out panel with a 4-module quiet zone. Never embed a raster QR.
- Profiles: `compact` (about 61 modules, reference 0.35 mm modules at 252 px) and `full` (about 81 modules, reference 0.263 mm). Compute the actual module size in mm from the real matrix size and the 252 px (0.84 in) drawing area. If below the configured minimum (default 0.33 mm), warn clearly and require an explicit acknowledgement that is stored in the export audit. Default to `compact`.
- Test round-trip in the test suite: render, decode with a pure-PHP decoder as a dev dependency (evaluate `khanamiryan/qrcode-detector-decoder`), and assert the decoded text equals the vCard.

### 3.5 PDF engine decision (Phase 2 spike, then implement behind an interface)

Define `CardPdfRenderer` (interface) with `renderArtwork(BusinessCard): string` and `renderFoilPlate(BusinessCard): string`. Run a time-boxed spike to decide the engine. Candidates: the PDF engine already in the repo (probably mPDF), TCPDF, or a thin custom content-stream writer on top of one of them. Build a capability matrix with VERIFIED evidence (vendor source or a generated test PDF you inspect), not memory. Test each against:

| # | Capability | Priority |
|---|---|---|
| 1 | Pure PHP, runs inside shared-hosting limits | MUST |
| 2 | DeviceCMYK fills, no RGB leaking in anywhere | MUST |
| 3 | Named spot colour `Silver_Foil` (Separation colourspace) with CMYK alternate | MUST (else deliver the foil plate PDF as the only foil instrument, and say so) |
| 4 | TrimBox and BleedBox and correct MediaBox | MUST |
| 5 | Crop marks in registration colour | MUST |
| 6 | Embedded, subset fonts (Montserrat, Cormorant Garamond), no substitution | MUST |
| 7 | Arbitrary vector paths (logo, icons, QR) with non-zero fill and clipping | MUST |
| 8 | Linear gradients in CMYK (back emblem) | SHOULD (fallback: flat fill, state the visual difference) |
| 9 | Overprint flag on fills | SHOULD (fallback: document that the printer sets overprint on the foil swatch) |
| 10 | Kerning and ligatures | SHOULD |
| 11 | Bengali shaping for optional `*_bn` fields (test conjuncts such as ক্ষ and শ্র with Noto Sans Bengali, OFL) | COULD, ship only if it renders correctly, otherwise leave the fields out and report |
| 12 | PDF/X output mode | COULD. Do NOT claim PDF/X compliance unless validated by a real preflight tool |

Record the decision and evidence in `docs/business-card/ENGINE_DECISION.md`. If no engine meets all MUSTs, stop and report options instead of compromising silently.

Fonts: use `tools/make_static_fonts.py` on a dev machine to create static TTFs, store them under the repo's font directory with the OFL licence texts. Variable fonts are not acceptable for embedding unless the engine demonstrably handles them.

### 3.6 PDF output requirements

Two files per card, generated from the same primitive list:

1. **Print artwork** (front page 1, back page 2): page 306 x 198 pt (trim 252 x 144 pt plus 9 pt bleed plus 18 pt marks area each side); all colour in CMYK from `card-spec.json`; foil objects in spot `Silver_Foil` with overprint where supported; reversed text as paper-white knock-out; small text and QR in K100 with overprint; crop marks 0.25 pt in registration colour starting at the bleed edge; a 4.5 pt slug label (side, trim size, bleed, colour model).
2. **Foil plate** (front page 1, back page 2): only the foil shapes in K100 on white with the same page geometry and marks.

Foil hairlines are drawn as filled rectangles at the configured thickness (0.75 pt default), not strokes. The front watermark is plain tone-on-tone ink, not spot UV, and is clipped to the bleed box. Logo: parse `assets/chrsd-logo.svg` once at build time into path data; use the single-colour silhouette for foil and watermark, and the gradient version (CMYK stops from the spec) for the small back emblem.

### 3.7 Filament UI

A `BusinessCard` resource (follow repo naming) with:
- Form sections: Person (including optional Bengali fields only if 3.5 item 11 passes), Contacts (repeaters with type, number, display), Web and LinkedIn, Address (max 3 lines), QR profile.
- A **live preview** (Livewire/Alpine) that renders the layout engine's primitive list as inline SVG, front and back side by side, at true proportions, with toggles for print guides (bleed, trim, safe area), and flat ink vs foil. Self-host any fonts. No CDN dependency in production. Reuse the visual language of `reference/preview-executive-card.html`.
- A validation panel listing layout, QR and consistency issues, split into errors (block export) and warnings.
- Actions: Download print artwork, Download foil plate, Approve for press (permission-gated, blocked by errors, records approver and time), Mark sent to press. Every download writes an audit row (3.2).
- Table with status badges and filters. Respect existing policies and the repo's permission package.

---

## 4. Tests and quality gates

Add tests in the repo's framework, covering at least:
- LinkedIn normalisation (including the doubled-path bug), phone normalisation, vCard escaping and types, consistency checker mismatch cases.
- Layout engine: overflow detection, reflow for 1, 5 and 8 rows, safe-area and minimum-size errors.
- QR: matrix to vCard round-trip decode; module-size calculation; acknowledgement requirement.
- PDF preflight (a service you also run after every export and store in the audit): page boxes present and correct, no RGB operators, `Silver_Foil` separation present, `All` separation for marks, all fonts embedded, file opens without syntax errors. Parse with a PHP-side library for tests; on a dev machine you may additionally run `pdfinfo -box`, `pdffonts`, `qpdf --check`, and render with `pdftoppm` to compare against the reference PDF visually.
- Authorisation: unauthorised users cannot view or download another person's card or its PDFs.
- Run the existing test suite and code-style tools. Nothing previously green may break.

---

## 5. cPanel deployment notes (deliver as `docs/business-card/DEPLOY.md`)

- Required PHP extensions and versions, and how to check them on the host.
- Writable engine temp directory and font directory, with permissions. No reliance on `/tmp` assumptions.
- Measure and report peak memory and generation time for one card (artwork plus plate) on a constrained setup (for example `memory_limit=128M`, `max_execution_time=30`). If it is tight, add result caching keyed by a hash of card data plus spec version plus engine version. Do not add a queue worker.
- `composer install --no-dev --optimize-autoloader`, `php artisan migrate` (additive only), `storage:link` only if a public disk is actually needed (PDFs stay private).
- Rollback steps.

---

## 6. Definition of done

- [ ] Phase 1 report delivered; engine decision documented with VERIFIED evidence
- [ ] A card created in Filament renders a live preview that matches `reference/preview-executive-card.html`
- [ ] Exported artwork PDF matches `reference/CHRSD_Card_Print_Artwork_Silver.pdf` visually when both are rendered at the same size (state the comparison method and result)
- [ ] Preflight passes on every export: CMYK only, boxes, spot and registration colourants, embedded fonts, vector QR
- [ ] The QR decodes to a vCard whose phones, emails and URLs match what is printed; the three known bugs in `card-spec.json` cannot recur (each has a test)
- [ ] Module-size and safe-area checks block or warn exactly as specified
- [ ] No runtime dependency on Chromium, Node, exec functions or a CDN
- [ ] Tests and style checks green; migrations additive and reversible
- [ ] Audit rows written for every export; files on a private disk; permissions enforced
- [ ] `ENGINE_DECISION.md` and `DEPLOY.md` committed

---

## 7. Final report format

1. **Discovery summary** (what existed, what you reused)
2. **Engine decision matrix** with evidence and fallbacks taken
3. **What changed** (files, migrations, config, packages added with versions and licences)
4. **Verification** (tests run, preflight output, visual comparison method and result, memory and time measurements)
5. **Differences from the reference PDF**, each with reason (for example embedded fonts instead of outlined text, flat instead of gradient emblem)
6. **Open risks and unknowns** (printer foil minimum, bleed convention, CMYK proof, QR density)
7. **Questions for the owner**, and **Suggestions** (out of scope ideas only)

If at any point the brief and reality conflict, stop and ask. Do not guess.
