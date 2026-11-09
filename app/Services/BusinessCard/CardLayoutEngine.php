<?php

namespace App\Services\BusinessCard;

/**
 * Turns the contact facts into drawing primitives for the front and back of the
 * executive card, and checks the layout against the spec.
 *
 * One primitive list drives both outputs: the PDF renderer draws it at the page's
 * points, and the on-screen preview draws the same list as SVG. The two cannot drift.
 *
 * Coordinates: the spec's design px (300 dpi, origin at the top-left of the trim,
 * y down). They are converted to PDF points (origin at the bottom-left of the page)
 * when a primitive is built, so every primitive is in points and in page coordinates.
 *
 * Primitive kinds:
 *   rect       x, y, w, h (pt), fill (colour), overprint, clip? (rect)
 *   path       ops (M/L/C/Z in pt), fill? (colour), stroke? (colour), strokeWidth,
 *              cap, join, overprint, clip? (rect)
 *   gradient   ops (pt), gradient {coords, stops}, overprint, clip? (rect)
 *   text       font (key), text, size (pt), x, baseline (pt), tracking (em), fill, overprint,
 *              bbox (px), slug? (bool, exempt from the minimum font size)
 *   qr         x, y, size (pt), modules, rows (bool[][]), fill, overprint
 *
 * Colour: {type: cmyk, c: [c,m,y,k]} (fractions) or {type: spot, name, tint, alt}.
 */
final class CardLayoutEngine
{
    public const PX = 0.24;

    public const TRIM_LEFT_PT = 27.0;

    public const TRIM_TOP_PT = 171.0;

    public const TRIM_W_PX = 1050.0;

    public const TRIM_H_PX = 600.0;

    public const BLEED_PT = 9.0;

    public const SLUG_PT = 27.0;

    public const MARK_OFFSET_PT = 9.0;

    public const MARK_LENGTH_PT = 13.5;

    public const MARK_WEIGHT_PT = 0.25;

    public const PAPER = ['type' => 'cmyk', 'c' => [0.0, 0.0, 0.0, 0.0]];

    /** @var list<array<string, mixed>> */
    private array $issues = [];

    private int $spaceRows = 0;

    public function __construct(
        private readonly CardSpec $spec,
        private readonly CardQrCode $qrCode,
        private readonly float $minFoilLinePt,
        private readonly float $minModuleMm,
        private readonly int $quietZoneModules,
        private readonly float $minFontPt,
        private readonly int $maxInkPercent,
    ) {}

    public static function fromConfig(CardSpec $spec, CardQrCode $qrCode): self
    {
        return new self(
            $spec,
            $qrCode,
            (float) config('business_card.foil_min_line_pt'),
            (float) config('business_card.qr_min_module_mm'),
            (int) config('business_card.qr_quiet_zone_modules'),
            (float) config('business_card.min_font_pt'),
            (int) config('business_card.max_total_ink_percent'),
        );
    }

    /**
     * @param  'artwork'|'plate'  $mode
     */
    public function build(CardContact $contact, string $vcard, string $profile, string $mode = 'artwork'): CardLayout
    {
        $this->issues = [];
        $qr = $this->qrCode->matrix($vcard);

        $front = $this->front($contact, 'front');
        $back = $this->back($contact, $qr, 'back');

        $this->validate($front, 'front');
        $this->validate($back, 'back');
        $this->validateQr($qr, $profile);
        $this->validateInk(array_merge($front, $back));

        return new CardLayout(
            front: $this->withSlug($front, 'front', $mode),
            back: $this->withSlug($back, 'back', $mode),
            issues: $this->issues,
            qr: $this->qrSummary($qr, $profile),
            specVersion: $this->spec->version,
            profile: $profile,
        );
    }

    /* ------------------------------------------------------------------ front */

    /** @return list<array<string, mixed>> */
    private function front(CardContact $contact, string $side): array
    {
        $out = [];

        // Forest background, full bleed.
        $out[] = $this->rect(18, 18, 270, 162, $this->ink('forest'), false, side: $side, name: 'bleed-background');

        // Tone-on-tone watermark, clipped to the bleed box (plain ink, not spot UV).
        foreach ($this->logoPaths(500, 60, 800, $this->ink('forest_tone'), $side) as $p) {
            $p['clip'] = $this->bleedRect();
            $out[] = $p;
        }

        // Silver foil emblem.
        foreach ($this->logoPaths(60, 58, 236, $this->foil(), $side, overprint: true) as $p) {
            $out[] = $p;
        }

        // Organisation name, three lines, knocked out of the forest.
        $org = $this->spec->get('front.org_name');
        foreach ($org['lines'] as $i => $line) {
            $lineTop = $org['first_line_top'] + $i * $org['size_px'] * $org['line_height'];
            $out[] = $this->text(
                $side,
                'serif_org',
                $line,
                $org['size_px'],
                $org['x'],
                $lineTop,
                $org['size_px'] * $org['line_height'],
                self::PAPER,
                false,
                0.0,
                'org-line-' . ($i + 1),
            );
        }

        // Silver foil hairline across the card.
        $hair = $this->spec->get('front.hairline_foil');
        $out[] = $this->hairlineH($hair['x1'], $hair['x2'], $hair['centre_y'], $hair['thickness_pt'], $this->foil(), true, $side, 'front-hairline');

        // Person name and title.
        $name = $this->spec->get('front.person_name');
        $out[] = $this->text(
            $side, 'sans_semibold', $contact->personName, $name['size_px'], $name['x'],
            $name['line_top'], $name['size_px'] * $name['line_height'], self::PAPER, false,
            $name['tracking_em'], 'person-name',
        );
        $title = $this->spec->get('front.person_title');
        $out[] = $this->text(
            $side, 'sans_semibold', $contact->personTitle, $title['size_px'], $title['x'],
            $title['line_top'], $title['size_px'] * $title['line_height'], self::PAPER, false,
            $title['tracking_em'], 'person-title',
        );

        return $out;
    }

    /* ------------------------------------------------------------------- back */

    /** @return list<array<string, mixed>> */
    private function back(CardContact $contact, array $qr, string $side): array
    {
        $out = [];

        $out[] = $this->rect(18, 18, 270, 162, $this->ink('pearl'), false, side: $side, name: 'bleed-background');

        // Contact rows: phones, emails, then web and LinkedIn. Tops reflow from the spec rules.
        $rowSpec = $this->spec->get('back.contact_rows');
        $rows = $this->contactRows($contact);
        $top = $rowSpec['first_row_top'];
        $previousGroup = null;
        $lastBottom = $top;
        foreach ($rows as $row) {
            if ($previousGroup !== null && $row['group'] !== $previousGroup) {
                $top += $rowSpec['group_gap_px'];
            }
            $previousGroup = $row['group'];

            $iconSpec = $rowSpec['icon'];
            $out = array_merge($out, $this->icon($row['icon'], $iconSpec['x'], $top + $iconSpec['top_offset_in_row'], $iconSpec['size_px'], $this->ink('icon_grey'), false, $side, 'icon-' . $row['icon']));

            $textSpec = $rowSpec['text'];
            $out[] = $this->text(
                $side, 'sans_regular', $row['text'], $textSpec['size_px'], $textSpec['x'],
                $top + $textSpec['line_top_offset_in_row'], $textSpec['size_px'] * $textSpec['line_height'],
                $this->ink('black'), true, 0.0, 'contact-' . $row['icon'],
            );

            $lastBottom = $top + $rowSpec['row_height_px'];
            $top += $rowSpec['row_height_px'];
        }
        $this->spaceRows = (int) $lastBottom;

        // Address: hairline, pin, and up to three lines.
        $addr = $this->spec->get('back.address');
        $hair = $this->spec->get('back.address_hairline_foil');
        $out[] = $this->hairlineH($hair['x1'], $hair['x2'], $hair['centre_y'], $hair['thickness_pt'], $this->foil(), true, $side, 'address-hairline');
        $out = array_merge($out, $this->icon('pin', $addr['icon']['x'], $addr['icon']['top'], 30, $this->ink('icon_grey'), false, $side, 'icon-pin'));
        $pitch = $addr['size_px'] * $addr['line_height'];
        foreach ($addr['lines'] as $i => $line) {
            $out[] = $this->text(
                $side, 'sans_regular', $line, $addr['size_px'], $addr['x'],
                $addr['first_line_top'] + $i * $pitch, $pitch, $this->ink('slate'), true, 0.0, 'address-line-' . ($i + 1),
            );
        }
        if (count($addr['lines']) > $addr['max_lines']) {
            $this->issue('error', 'address_too_many_lines', $side, "The address has more than {$addr['max_lines']} lines.");
        }

        // Vertical foil divider.
        $div = $this->spec->get('back.vertical_divider_foil');
        $out[] = $this->rect(
            $this->xPt($div['centre_x']) - $div['thickness_pt'] / 2,
            $this->yPt($div['y2']),
            $div['thickness_pt'],
            ($div['y2'] - $div['y1']) * self::PX,
            $this->foil(), true, side: $side, name: 'vertical-divider',
        );

        // Full-colour gradient emblem.
        $emblem = $this->spec->get('back.emblem_colour');
        foreach ($this->logoPaths($emblem['left'], $emblem['top'], $emblem['width_px'], null, $side, gradient: true) as $p) {
            $out[] = $p;
        }

        // QR panel: paper knock-out, foil frame, then the modules.
        $panel = $this->spec->get('back.qr_panel');
        $o = $panel['outer'];
        $out[] = $this->rect($this->xPt($o['x1']), $this->yPt($o['y2']), ($o['x2'] - $o['x1']) * self::PX, ($o['y2'] - $o['y1']) * self::PX, self::PAPER, false, side: $side, name: 'qr-panel');
        $t = $panel['frame_foil_thickness_pt'];
        $out[] = $this->hairlineH($o['x1'], $o['x2'], $o['y1'] + $t / self::PX / 2, $t, $this->foil(), true, $side, 'qr-frame-top');
        $out[] = $this->hairlineH($o['x1'], $o['x2'], $o['y2'] - $t / self::PX / 2, $t, $this->foil(), true, $side, 'qr-frame-bottom');
        $out[] = $this->rect($this->xPt($o['x1']), $this->yPt($o['y2']), $t, ($o['y2'] - $o['y1']) * self::PX, $this->foil(), true, side: $side, name: 'qr-frame-left');
        $out[] = $this->rect($this->xPt($o['x2']) - $t, $this->yPt($o['y2']), $t, ($o['y2'] - $o['y1']) * self::PX, $this->foil(), true, side: $side, name: 'qr-frame-right');

        $origin = $panel['qr_origin'];
        $out[] = [
            'kind' => 'qr',
            'x' => $this->xPt($origin[0]),
            'y' => $this->yPt($origin[1] + $panel['qr_size_px']),
            'size' => $this->pxToPt($panel['qr_size_px']),
            'modules' => $qr['modules'],
            'rows' => $qr['rows'],
            'fill' => $this->ink('black'),
            'overprint' => true,
            'name' => 'qr',
        ];

        // Caption under the panel, centred.
        $cap = $panel['caption'];
        $capWidth = $this->measurePx($cap['text'], 'sans_regular', $cap['size_px'], 0.0);
        $centre = ($o['x1'] + $o['x2']) / 2;
        $out[] = $this->text(
            $side, 'sans_regular', $cap['text'], $cap['size_px'], $centre - $capWidth / 2,
            $cap['line_top'], $cap['size_px'], $this->ink('slate'), true, 0.0, 'qr-caption',
        );

        return $out;
    }

    /**
     * Rows in order: phones (office, mobile, WhatsApp), emails (one row each), then
     * web (website and LinkedIn). Display text follows the client's style.
     *
     * @return list<array{group: string, icon: string, text: string}>
     */
    private function contactRows(CardContact $contact): array
    {
        $rows = [];
        foreach ([[CardContact::PHONE_OFFICE, 'tel'], [CardContact::PHONE_MOBILE, 'cell'], [CardContact::PHONE_WHATSAPP, 'wa']] as [$type, $icon]) {
            foreach ($contact->phonesOfType($type) as $phone) {
                $rows[] = ['group' => 'phones', 'icon' => $icon, 'text' => $phone['display']];
            }
        }
        foreach ($contact->emails as $email) {
            $rows[] = ['group' => 'emails', 'icon' => 'mail', 'text' => $email['address']];
        }
        if ($contact->website !== null && $contact->website !== '') {
            $rows[] = ['group' => 'web', 'icon' => 'web', 'text' => preg_replace('#^https?://(www\.)?#i', '', rtrim($contact->website, '/'))];
        }
        if ($contact->linkedinUrl !== null) {
            $rows[] = ['group' => 'web', 'icon' => 'in', 'text' => preg_replace('#^https?://(www\.)?#i', '', $contact->linkedinUrl)];
        }

        return $rows;
    }

    /* ------------------------------------------------------- primitive helpers */

    /** @return array<string, mixed> */
    private function ink(string $name): array
    {
        return ['type' => 'cmyk', 'name' => $name, 'c' => $this->spec->colour($name)];
    }

    /** @return array<string, mixed> */
    private function foil(): array
    {
        return [
            'type' => 'spot',
            'name' => $this->spec->get('colours.silver_foil.spot_name'),
            'tint' => 1.0,
            'alt' => array_map(fn ($v) => $v / 100, $this->spec->get('colours.silver_foil.alt_cmyk_for_screen')),
        ];
    }

    private function bleedRect(): array
    {
        return ['x' => 18.0, 'y' => 18.0, 'w' => 270.0, 'h' => 162.0];
    }

    private function xPt(float $px): float
    {
        return self::TRIM_LEFT_PT + $px * self::PX;
    }

    private function yPt(float $py): float
    {
        return self::TRIM_TOP_PT - $py * self::PX;
    }

    private function pxToPt(float $px): float
    {
        return $px * self::PX;
    }

    /** @return array<string, mixed> */
    private function rect(float $x, float $y, float $w, float $h, array $fill, bool $overprint, string $side, string $name): array
    {
        return ['kind' => 'rect', 'x' => $x, 'y' => $y, 'w' => $w, 'h' => $h, 'fill' => $fill, 'overprint' => $overprint, 'side' => $side, 'name' => $name];
    }

    /**
     * A horizontal foil line, drawn as a filled rectangle at the given thickness in points.
     *
     * @return array<string, mixed>
     */
    private function hairlineH(float $x1px, float $x2px, float $centrePx, float $thicknessPt, array $fill, bool $overprint, string $side, string $name): array
    {
        if ($thicknessPt < $this->minFoilLinePt) {
            $this->issue('error', 'foil_line_below_minimum', $side, "Foil line [{$name}] is {$thicknessPt} pt; the printer minimum is {$this->minFoilLinePt} pt.");
        }
        $half = $thicknessPt / self::PX / 2;

        return $this->rect($this->xPt($x1px), $this->yPt($centrePx + $half), ($x2px - $x1px) * self::PX, $thicknessPt, $fill, $overprint, $side, $name);
    }

    /**
     * Text positioned by its line box, using the same baseline rule as the reference build.
     *
     * @return array<string, mixed>
     */
    private function text(string $side, string $fontKey, string $text, float $sizePx, float $xPx, float $lineTopPx, float $lineHeightPx, array $fill, bool $overprint, float $tracking, string $name, bool $slug = false): array
    {
        $font = CardAssets::font($fontKey);
        if (! $font->covers($text)) {
            $this->issue('error', 'missing_glyph', $side, "The font for [{$name}] has no glyph for some characters in [{$text}].");
        }

        $sizePt = $sizePx * self::PX;
        // Line box uses the font's ascent and descent, as the reference build does.
        $asc = $sizePx * $font->ascent() / 1000;
        $desc = $sizePx * -$font->descent() / 1000;
        $baselinePx = $lineTopPx + ($lineHeightPx - ($asc + $desc)) / 2 + $asc;
        $widthPx = $this->measurePx($text, $fontKey, $sizePx, $tracking);

        // Ink box for validation: cap height above the baseline, a descender estimate below.
        // Full ascent and descent would call tight display leading an overlap when no ink collides.
        $inkTop = $sizePx * $font->capHeight() / 1000;
        $inkBottom = $sizePx * 0.22;
        $bbox = ['x0' => $xPx, 'x1' => $xPx + $widthPx, 'y0' => $baselinePx - $inkTop, 'y1' => $baselinePx + $inkBottom];

        if ($sizePt < $this->minFontPt && ! $slug) {
            $this->issue('error', 'font_below_minimum', $side, "[{$name}] is {$sizePt} pt; the minimum is {$this->minFontPt} pt.");
        }

        return [
            'kind' => 'text',
            'font' => $fontKey,
            'text' => $text,
            'size' => $sizePt,
            'x' => $this->xPt($xPx),
            'baseline' => $this->yPt($baselinePx),
            'tracking' => $tracking,
            'fill' => $fill,
            'overprint' => $overprint,
            'bbox' => $bbox,
            'side' => $side,
            'name' => $name,
            'slug' => $slug,
        ];
    }

    private function measurePx(string $text, string $fontKey, float $sizePx, float $tracking): float
    {
        return CardAssets::font($fontKey)->measure($text, $sizePx * self::PX, $tracking) / self::PX;
    }

    /**
     * An icon: its stroke paths, scaled from the 24-unit design grid.
     *
     * @return list<array<string, mixed>>
     */
    private function icon(string $name, float $leftPx, float $topPx, float $sizePx, array $stroke, bool $overprint, string $side, string $label): array
    {
        $scale = $sizePx / 24.0;
        $out = [];
        foreach (CardAssets::icon($name) as $subpath) {
            $ops = [];
            foreach ($subpath as $cmd) {
                $ops[] = $this->mapOps($cmd, fn (float $x, float $y) => [$this->xPt($leftPx + $x * $scale), $this->yPt($topPx + $y * $scale)]);
            }
            $out[] = [
                'kind' => 'path',
                'ops' => $ops,
                'fill' => null,
                'stroke' => $stroke,
                'strokeWidth' => 1.6 * $scale * self::PX,
                'cap' => 1,
                'join' => 1,
                'overprint' => $overprint,
                'side' => $side,
                'name' => $label,
            ];
        }

        return $out;
    }

    /**
     * Logo silhouette (fill) or full-colour gradient, drawn in the box given in design px.
     *
     * @return list<array<string, mixed>>
     */
    private function logoPaths(float $leftPx, float $topPx, float $widthPx, ?array $fill, string $side, bool $overprint = false, bool $gradient = false): array
    {
        $logo = CardAssets::logo();
        $vbW = $logo['viewBox'][2];
        $scale = $widthPx * self::PX / $vbW;
        $map = fn (float $x, float $y) => [$this->xPt($leftPx) + $x * $scale, $this->yPt($topPx) - $y * $scale];

        $out = [];
        foreach ($logo['paths'] as $i => $path) {
            $ops = array_map(fn ($cmd) => $this->mapOps($cmd, $map), $path['d']);
            if ($gradient && $path['gradient'] !== null) {
                $g = $logo['gradients'][$path['gradient']];
                [$x1, $y1] = $map($g['x1'], $g['y1']);
                [$x2, $y2] = $map($g['x2'], $g['y2']);
                $stops = [];
                foreach ($g['stops'] as [$offset, $hex]) {
                    $stops[] = array_merge([(float) $offset], $this->spec->gradientStopCmyk($hex));
                }
                $out[] = [
                    'kind' => 'gradient',
                    'ops' => $ops,
                    'gradient' => ['coords' => [$x1, $y1, $x2, $y2], 'stops' => $stops],
                    'overprint' => false,
                    'side' => $side,
                    'name' => 'emblem-gradient-' . $i,
                ];

                continue;
            }
            if ($fill !== null) {
                $out[] = [
                    'kind' => 'path',
                    'ops' => $ops,
                    'fill' => $fill,
                    'stroke' => null,
                    'overprint' => $overprint,
                    'side' => $side,
                    'name' => 'logo-' . $i,
                ];
            }
        }

        return $out;
    }

    /**
     * Crop marks and the slug label, added once the artwork is laid out.
     *
     * @param  list<array<string, mixed>>  $primitives
     * @return list<array<string, mixed>>
     */
    private function withSlug(array $primitives, string $side, string $mode): array
    {
        $reg = ['type' => 'spot', 'name' => 'All', 'tint' => 1.0, 'alt' => [1.0, 1.0, 1.0, 1.0]];
        $trimW = $this->spec->trimPt()[0];
        $trimH = $this->spec->trimPt()[1];
        $x0 = self::TRIM_LEFT_PT;
        $y0 = self::TRIM_LEFT_PT;
        $x1 = $x0 + $trimW;
        $y1 = $y0 + $trimH;
        $off = self::MARK_OFFSET_PT;
        $len = self::MARK_LENGTH_PT;

        $corners = [[$x0, $y0, -1, -1], [$x1, $y0, 1, -1], [$x0, $y1, -1, 1], [$x1, $y1, 1, 1]];
        foreach ($corners as [$x, $y, $dx, $dy]) {
            $primitives[] = [
                'kind' => 'path', 'ops' => [['M', $x + $dx * $off, $y], ['L', $x + $dx * ($off + $len), $y]],
                'fill' => null, 'stroke' => $reg, 'strokeWidth' => self::MARK_WEIGHT_PT, 'cap' => 0, 'join' => 0,
                'overprint' => false, 'side' => $side, 'name' => 'crop-mark-h',
            ];
            $primitives[] = [
                'kind' => 'path', 'ops' => [['M', $x, $y + $dy * $off], ['L', $x, $y + $dy * ($off + $len)]],
                'fill' => null, 'stroke' => $reg, 'strokeWidth' => self::MARK_WEIGHT_PT, 'cap' => 0, 'join' => 0,
                'overprint' => false, 'side' => $side, 'name' => 'crop-mark-v',
            ];
        }

        $label = $side === 'front'
            ? ($mode === 'plate' ? 'FRONT | SILVER FOIL PLATE | black = foil | trim 3.5x2 in' : 'FRONT | trim 3.5x2 in | bleed 0.125 in | CMYK + spot Silver_Foil')
            : ($mode === 'plate' ? 'BACK | SILVER FOIL PLATE | black = foil | trim 3.5x2 in' : 'BACK | trim 3.5x2 in | bleed 0.125 in | CMYK + spot Silver_Foil');
        $primitives[] = [
            'kind' => 'text', 'font' => 'sans_regular', 'text' => $label, 'size' => 4.5,
            'x' => 33.0, 'baseline' => 8.76, 'tracking' => 0.0,
            'fill' => ['type' => 'cmyk', 'name' => 'black', 'c' => [0.0, 0.0, 0.0, 1.0]],
            'overprint' => false, 'bbox' => null, 'side' => $side, 'name' => 'slug', 'slug' => true,
        ];

        return $primitives;
    }

    /** Map one path command's coordinates through a point transform. */
    private function mapOps(array $cmd, callable $map): array
    {
        $op = $cmd[0];
        if ($op === 'Z') {
            return ['Z'];
        }
        if ($op === 'C') {
            [$x1, $y1] = $map($cmd[1], $cmd[2]);
            [$x2, $y2] = $map($cmd[3], $cmd[4]);
            [$x3, $y3] = $map($cmd[5], $cmd[6]);

            return ['C', $x1, $y1, $x2, $y2, $x3, $y3];
        }
        [$x, $y] = $map($cmd[1], $cmd[2]);

        return [$op, $x, $y];
    }

    /* -------------------------------------------------------------- validation */

    /** @param  list<array<string, mixed>>  $primitives */
    private function validate(array $primitives, string $side): void
    {
        $safe = (float) $this->spec->get('page.safe_inset_px');
        $texts = array_values(array_filter($primitives, fn (array $p) => $p['kind'] === 'text' && ! $p['slug']));

        foreach ($texts as $t) {
            $b = $t['bbox'];
            if ($b['x0'] < $safe || $b['x1'] > self::TRIM_W_PX - $safe || $b['y0'] < $safe || $b['y1'] > self::TRIM_H_PX - $safe) {
                $this->issue('error', 'text_outside_safe_area', $side, "[{$t['name']}] crosses the safe area (45 px inside the trim).");
            }
        }

        // Text may not overlap other text on the same side.
        for ($i = 0; $i < count($texts); $i++) {
            for ($j = $i + 1; $j < count($texts); $j++) {
                if ($this->boxesOverlap($texts[$i]['bbox'], $texts[$j]['bbox'])) {
                    $this->issue('error', 'text_overlap', $side, "[{$texts[$i]['name']}] overlaps [{$texts[$j]['name']}].");
                }
            }
        }

        // Back column: contact and address text must stop before the vertical divider.
        if ($side === 'back') {
            foreach ($texts as $t) {
                if ($t['name'] !== 'qr-caption' && $t['bbox']['x1'] > 660) {
                    $this->issue('error', 'text_into_divider', $side, "[{$t['name']}] runs into the vertical divider.");
                }
            }
            $addressTop = (float) $this->spec->get('back.address_hairline_foil.centre_y') - 8;
            if ($this->spaceRows > $addressTop) {
                $this->issue('error', 'contact_rows_overflow', $side, 'The contact rows run into the address block. Remove a row or shorten the groups.');
            }
        }
    }

    private function boxesOverlap(array $a, array $b): bool
    {
        return $a['x0'] < $b['x1'] && $b['x0'] < $a['x1'] && $a['y0'] < $b['y1'] && $b['y0'] < $a['y1'];
    }

    /** @param  array{modules: int, rows: list<list<bool>>}  $qr */
    private function validateQr(array $qr, string $profile): void
    {
        $n = $qr['modules'];
        $moduleMm = $this->moduleMm($n);
        if ($moduleMm < $this->minModuleMm) {
            $this->issue('warning', 'qr_module_below_minimum', 'back',
                sprintf('The QR (%s profile, %d modules) has %.3f mm modules; the minimum is %.2f mm. Export needs an acknowledgement.', $profile, $n, $moduleMm, $this->minModuleMm),
                requiresAck: true,
            );
        }

        $marginPx = (($this->spec->get('back.qr_panel.outer.x2') - $this->spec->get('back.qr_panel.outer.x1')) - $this->spec->get('back.qr_panel.qr_size_px')) / 2;
        $marginModules = $marginPx / ($this->spec->get('back.qr_panel.qr_size_px') / $n);
        if ($marginModules < $this->quietZoneModules) {
            $this->issue('error', 'qr_quiet_zone_short', 'back', sprintf('The QR quiet zone is %.1f modules; %d are needed.', $marginModules, $this->quietZoneModules));
        }
    }

    /** Module size in millimetres: the symbol spans the 252 px drawing area. */
    public function moduleMm(int $modules): float
    {
        // Design px -> points -> millimetres (1 pt = 25.4 / 72 mm).
        $sideMm = $this->spec->get('back.qr_panel.qr_size_px') * self::PX * 25.4 / 72;

        return $sideMm / $modules;
    }

    /** @param  list<array<string, mixed>>  $primitives */
    private function validateInk(array $primitives): void
    {
        $seen = [];
        foreach ($primitives as $p) {
            $fill = $p['fill'] ?? null;
            if (is_array($fill) && ($fill['type'] ?? null) === 'cmyk') {
                $seen[$fill['name'] ?? json_encode($fill['c'])] = $fill['c'];
            }
        }
        foreach ($seen as $name => $c) {
            $total = array_sum($c) * 100;
            if ($total > $this->maxInkPercent) {
                $this->issue('error', 'ink_over_maximum', null, sprintf('[%s] uses %.0f%% total ink; the maximum is %d%%.', $name, $total, $this->maxInkPercent));
            }
        }
    }

    /** @return array{modules: int, module_mm: float, quiet_zone_modules: int, profile: string} */
    private function qrSummary(array $qr, string $profile): array
    {
        return [
            'modules' => $qr['modules'],
            'module_mm' => round($this->moduleMm($qr['modules']), 3),
            'quiet_zone_modules' => $this->quietZoneModules,
            'profile' => $profile,
        ];
    }

    private function issue(string $severity, string $code, ?string $side, string $message, bool $requiresAck = false): void
    {
        $this->issues[] = [
            'severity' => $severity,
            'code' => $code,
            'side' => $side,
            'message' => $message,
            'requires_acknowledgement' => $requiresAck,
        ];
    }
}
