<?php

namespace App\Services\BusinessCard\Pdf;

use InvalidArgumentException;
use RuntimeException;

/**
 * A static TrueType font: metrics for measuring text, and a subset for embedding.
 *
 * Measuring and embedding use the same parsed tables, so the widths the layout
 * engine validates are the widths the PDF renders. Only static outline (glyf)
 * fonts are accepted. Variable fonts must be instanced first (see
 * docs/business-card/tools/make_static_fonts.py).
 *
 * Kerning (GPOS pair adjustment) and ligatures (GSUB) are not applied. The
 * spec lists them as SHOULD, and the fallback is documented in ENGINE_DECISION.md.
 */
final class TrueTypeFont
{
    private string $data;

    /** @var array<string, array{offset: int, length: int}> */
    private array $tables = [];

    private int $unitsPerEm;

    private int $indexToLocFormat;

    private int $numGlyphs;

    private int $numberOfHMetrics;

    /** @var array{0: int, 1: int, 2: int, 3: int} */
    private array $bbox;

    private int $ascender;

    private int $descender;

    private int $capHeight;

    private string $psName;

    /** @var array<int, int> codepoint => original glyph id */
    private array $cmap = [];

    /** @var array<int, int> original glyph id => new (subset) glyph id */
    private array $origToNew = [0 => 0];

    /** @var array<int, int> new glyph id => original glyph id */
    private array $newToOrig = [0 => 0];

    /** @var array<int, int> new glyph id => unicode codepoint (first one used) */
    private array $unicodeByNew = [];

    public function __construct(string $bytes)
    {
        $this->data = $bytes;
        $this->readDirectory();

        $head = $this->table('head');
        $this->unitsPerEm = $this->u16($head, 18);
        $this->bbox = [
            $this->s16($head, 36),
            $this->s16($head, 38),
            $this->s16($head, 40),
            $this->s16($head, 42),
        ];
        $this->indexToLocFormat = $this->s16($head, 50);

        $this->numGlyphs = $this->u16($this->table('maxp'), 4);

        $hhea = $this->table('hhea');
        $this->ascender = $this->s16($hhea, 4);
        $this->descender = $this->s16($hhea, 6);
        $this->numberOfHMetrics = $this->u16($hhea, 34);

        $os2 = $this->tables['OS/2'] ?? null;
        $this->capHeight = ($os2 !== null && $os2['length'] >= 90)
            ? $this->s16($this->table('OS/2'), 88)
            : (int) round($this->unitsPerEm * 0.7);

        $this->psName = $this->readPostScriptName();
        $this->cmap = isset($this->tables['cmap']) ? $this->readCmap() : [];
    }

    public static function fromFile(string $path): self
    {
        $bytes = file_get_contents($path);
        if ($bytes === false) {
            throw new RuntimeException("Cannot read font file [{$path}].");
        }

        return new self($bytes);
    }

    public function postScriptName(): string
    {
        return $this->psName;
    }

    /** True when every character of the string has a glyph in this font. */
    public function covers(string $utf8): bool
    {
        foreach ($this->codepoints($utf8) as $cp) {
            if (! isset($this->cmap[$cp])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Advance width of the string at the given size, in points. Includes the
     * tracking that the PDF applies after every glyph (Tc), so this equals the
     * distance the text advances on the page.
     */
    public function measure(string $utf8, float $sizePt, float $tracking = 0.0): float
    {
        $units = 0;
        $count = 0;
        foreach ($this->codepoints($utf8) as $cp) {
            $units += $this->advance($this->glyphFor($cp));
            $count++;
        }

        return $units * $sizePt / $this->unitsPerEm + $tracking * $sizePt * $count;
    }

    /** Ascent in PDF glyph space (1000 units per em). */
    public function ascent(): int
    {
        return $this->scale($this->ascender);
    }

    /** Descent in PDF glyph space (negative, 1000 units per em). */
    public function descent(): int
    {
        return $this->scale($this->descender);
    }

    public function capHeight(): int
    {
        return $this->scale($this->capHeight);
    }

    /** @return array{0: int, 1: int, 2: int, 3: int} */
    public function bbox(): array
    {
        return array_map(fn (int $v) => $this->scale($v), $this->bbox);
    }

    /**
     * Encode a string for a Tj operator in Identity-H: four hex digits per glyph,
     * using subset glyph ids. Records each glyph so the subset includes it.
     */
    public function encode(string $utf8): string
    {
        $hex = '';
        foreach ($this->codepoints($utf8) as $cp) {
            $orig = $this->glyphFor($cp);
            if (! isset($this->origToNew[$orig])) {
                $new = count($this->newToOrig);
                $this->origToNew[$orig] = $new;
                $this->newToOrig[$new] = $orig;
                $this->unicodeByNew[$new] = $cp;
            }

            $hex .= sprintf('%04X', $this->origToNew[$orig]);
        }

        return $hex;
    }

    /**
     * Build the embedded subset: a TrueType file holding the glyphs used so far
     * (plus any components of composite glyphs), with its PDF widths and ToUnicode.
     *
     * @return array{name: string, bytes: string, length: int, widths: list<string>, toUnicode: string}
     */
    public function subset(): array
    {
        $this->closeOverComponents();

        $count = count($this->newToOrig);
        $glyphs = [];
        for ($new = 0; $new < $count; $new++) {
            $glyphs[$new] = $this->glyphData($this->newToOrig[$new], $this->origToNew);
        }

        // glyf and loca (long format).
        $glyf = '';
        $loca = pack('N', 0);
        foreach ($glyphs as $g) {
            $glyf .= $g;
            while (strlen($glyf) % 4 !== 0) {
                $glyf .= "\0";
            }
            $loca .= pack('N', strlen($glyf));
        }

        // hmtx: advance and lsb for each subset glyph, from the original font.
        $hmtx = '';
        foreach ($this->newToOrig as $orig) {
            $hmtx .= pack('n', $this->advance($orig)) . pack('n', $this->lsb($orig) & 0xFFFF);
        }

        $head = $this->table('head');
        $head = substr_replace($head, pack('n', 1), 50, 2);  // indexToLocFormat = 1 (long)
        $head = substr_replace($head, "\0\0\0\0", 8, 4);     // checkSumAdjustment, filled after assembly

        $maxp = $this->table('maxp');
        $maxp = substr_replace($maxp, pack('n', $count), 4, 2);

        $hhea = $this->table('hhea');
        $hhea = substr_replace($hhea, pack('n', $count), 34, 2);

        $tables = [
            'OS/2' => $this->table('OS/2'),
            'cvt ' => $this->optionalTable('cvt '),
            'fpgm' => $this->optionalTable('fpgm'),
            'glyf' => $glyf,
            'head' => $head,
            'hhea' => $hhea,
            'hmtx' => $hmtx,
            'loca' => $loca,
            'maxp' => $maxp,
            'post' => pack('N', 0x00030000) . str_repeat("\0", 28),
            'prep' => $this->optionalTable('prep'),
        ];
        $tables = array_filter($tables, fn ($t) => $t !== null);
        ksort($tables);

        $bytes = $this->assembleFont($tables);

        $name = $this->subsetName($count);

        return [
            'name' => $name,
            'bytes' => $bytes,
            'length' => strlen($bytes),
            'widths' => $this->widths(),
            'toUnicode' => $this->toUnicodeCMap(),
        ];
    }

    /** Glyph id of a codepoint in the full font (0 when absent). */
    private function glyphFor(int $codepoint): int
    {
        if (! isset($this->cmap[$codepoint])) {
            throw new InvalidArgumentException(sprintf(
                'Font %s has no glyph for U+%04X.',
                $this->psName,
                $codepoint,
            ));
        }

        return $this->cmap[$codepoint];
    }

    /** @return list<int> */
    private function codepoints(string $utf8): array
    {
        $cps = [];
        foreach (preg_split('//u', $utf8, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $ch) {
            $cps[] = mb_ord($ch, 'UTF-8');
        }

        return $cps;
    }

    private function scale(int $units): int
    {
        return (int) round($units * 1000 / $this->unitsPerEm);
    }

    private function advance(int $glyph): int
    {
        $hmtx = $this->table('hmtx');
        $index = min($glyph, $this->numberOfHMetrics - 1);

        return $this->u16($hmtx, $index * 4);
    }

    private function lsb(int $glyph): int
    {
        $hmtx = $this->table('hmtx');
        if ($glyph < $this->numberOfHMetrics) {
            return $this->s16($hmtx, $glyph * 4 + 2);
        }

        // Glyphs beyond numberOfHMetrics share the last advance; their lsb follows in an array.
        return $this->s16($hmtx, $this->numberOfHMetrics * 4 + ($glyph - $this->numberOfHMetrics) * 2);
    }

    /**
     * Raw glyf bytes for an original glyph, with composite component indices
     * rewritten to subset ids.
     *
     * @param  array<int, int>  $origToNew
     */
    private function glyphData(int $orig, array $origToNew): string
    {
        $loca = $this->table('loca');
        if ($this->indexToLocFormat === 0) {
            $start = $this->u16($loca, $orig * 2) * 2;
            $end = $this->u16($loca, ($orig + 1) * 2) * 2;
        } else {
            $start = $this->u32($loca, $orig * 4);
            $end = $this->u32($loca, ($orig + 1) * 4);
        }

        $glyf = $this->table('glyf');
        $glyph = substr($glyf, $start, $end - $start);
        if ($glyph === '' || $this->s16($glyph, 0) >= 0) {
            return $glyph;
        }

        // Composite glyph: walk the components and rewrite each glyphIndex.
        $pos = 10;
        do {
            $flags = $this->u16($glyph, $pos);
            $component = $this->u16($glyph, $pos + 2);
            $glyph = substr_replace($glyph, pack('n', $origToNew[$component] ?? 0), $pos + 2, 2);

            $pos += 4;
            $pos += ($flags & 0x0001) ? 4 : 2; // ARG_1_AND_2_ARE_WORDS
            if ($flags & 0x0008) {             // WE_HAVE_A_SCALE
                $pos += 2;
            } elseif ($flags & 0x0040) {       // WE_HAVE_AN_X_AND_Y_SCALE
                $pos += 4;
            } elseif ($flags & 0x0080) {       // WE_HAVE_A_TWO_BY_TWO
                $pos += 8;
            }
        } while ($flags & 0x0020);             // MORE_COMPONENTS

        return $glyph;
    }

    /** Add component glyphs of composite subset glyphs so the subset is self-contained. */
    private function closeOverComponents(): void
    {
        $queue = array_values($this->newToOrig);
        while ($queue !== []) {
            $orig = array_shift($queue);
            foreach ($this->componentsOf($orig) as $component) {
                if (! isset($this->origToNew[$component])) {
                    $new = count($this->newToOrig);
                    $this->origToNew[$component] = $new;
                    $this->newToOrig[$new] = $component;
                    $queue[] = $component;
                }
            }
        }
    }

    /** @return list<int> */
    private function componentsOf(int $orig): array
    {
        $loca = $this->table('loca');
        if ($this->indexToLocFormat === 0) {
            $start = $this->u16($loca, $orig * 2) * 2;
            $end = $this->u16($loca, ($orig + 1) * 2) * 2;
        } else {
            $start = $this->u32($loca, $orig * 4);
            $end = $this->u32($loca, ($orig + 1) * 4);
        }

        $glyph = substr($this->table('glyf'), $start, $end - $start);
        if ($glyph === '' || $this->s16($glyph, 0) >= 0) {
            return [];
        }

        $components = [];
        $pos = 10;
        do {
            $flags = $this->u16($glyph, $pos);
            $components[] = $this->u16($glyph, $pos + 2);
            $pos += 4;
            $pos += ($flags & 0x0001) ? 4 : 2;
            if ($flags & 0x0008) {
                $pos += 2;
            } elseif ($flags & 0x0040) {
                $pos += 4;
            } elseif ($flags & 0x0080) {
                $pos += 8;
            }
        } while ($flags & 0x0020);

        return $components;
    }

    /** @return list<string> */
    private function widths(): array
    {
        $parts = [];
        $count = count($this->newToOrig);
        for ($new = 0; $new < $count; $new++) {
            $parts[] = $new . ' [' . $this->scale($this->advance($this->newToOrig[$new])) . ']';
        }

        return $parts;
    }

    private function toUnicodeCMap(): string
    {
        $pairs = [];
        foreach ($this->unicodeByNew as $new => $cp) {
            $pairs[] = sprintf('<%04X> <%04X>', $new, $cp);
        }

        $body = '';
        foreach (array_chunk($pairs, 100) as $chunk) {
            $body .= count($chunk) . " beginbfchar\n" . implode("\n", $chunk) . "\nendbfchar\n";
        }

        return "/CIDInit /ProcSet findresource begin\n12 dict begin\nbegincmap\n"
            . "/CIDSystemInfo << /Registry (Adobe) /Ordering (UCS) /Supplement 0 >> def\n"
            . "/CMapName /Adobe-Identity-UCS def\n/CMapType 2 def\n"
            . "1 begincodespacerange\n<0000> <FFFF>\nendcodespacerange\n"
            . $body
            . "endcmap\nCMapName currentdict /CMap defineresource pop\nend\nend\n";
    }

    private function subsetName(int $glyphCount): string
    {
        $seed = $this->psName . '|' . implode(',', $this->newToOrig);
        $tag = '';
        $hash = md5($seed);
        for ($i = 0; $i < 6; $i++) {
            $tag .= chr(65 + (hexdec($hash[$i]) % 26));
        }

        $clean = preg_replace('/[^A-Za-z0-9]/', '', $this->psName) ?: 'Font';

        return $tag . '+' . $clean;
    }

    /**
     * @param  array<string, string>  $tables  tag => bytes, sorted by tag
     */
    private function assembleFont(array $tables): string
    {
        $numTables = count($tables);
        $searchRange = 16 * (1 << (int) floor(log($numTables, 2)));
        $entrySelector = (int) floor(log($numTables, 2));
        $rangeShift = $numTables * 16 - $searchRange;

        $header = pack('N', 0x00010000) . pack('nnnn', $numTables, $searchRange, $entrySelector, $rangeShift);

        $offset = 12 + 16 * $numTables;
        $directory = '';
        $body = '';
        $headOffset = null;
        foreach ($tables as $tag => $bytes) {
            $padded = $bytes;
            while (strlen($padded) % 4 !== 0) {
                $padded .= "\0";
            }

            if ($tag === 'head') {
                $headOffset = $offset;
            }

            $directory .= $tag . pack('N', self::checksum($padded)) . pack('N', $offset) . pack('N', strlen($bytes));
            $body .= $padded;
            $offset += strlen($padded);
        }

        $font = $header . $directory . $body;

        if ($headOffset !== null) {
            $adjustment = (0xB1B0AFBA - self::checksum($font)) & 0xFFFFFFFF;
            $font = substr_replace($font, pack('N', $adjustment), $headOffset + 8, 4);
        }

        return $font;
    }

    private static function checksum(string $bytes): int
    {
        $sum = 0;
        $padded = $bytes;
        while (strlen($padded) % 4 !== 0) {
            $padded .= "\0";
        }
        $words = unpack('N*', $padded);
        foreach ($words as $w) {
            $sum = ($sum + $w) & 0xFFFFFFFF;
        }

        return $sum;
    }

    private function readDirectory(): void
    {
        if (strlen($this->data) < 12) {
            throw new RuntimeException('Font file is too short.');
        }

        $sfnt = $this->u32($this->data, 0);
        if ($sfnt !== 0x00010000 && $sfnt !== 0x74727565) {
            throw new RuntimeException('Only static TrueType (glyf) fonts are supported.');
        }

        $numTables = $this->u16($this->data, 4);
        for ($i = 0; $i < $numTables; $i++) {
            $entry = 12 + $i * 16;
            $tag = substr($this->data, $entry, 4);
            $this->tables[$tag] = [
                'offset' => $this->u32($this->data, $entry + 8),
                'length' => $this->u32($this->data, $entry + 12),
            ];
        }

        // cmap is required for a full font. A subset written by subset() has none, and
        // it is only ever embedded, so it is accepted for re-parsing.
        foreach (['head', 'maxp', 'hhea', 'hmtx', 'loca', 'glyf'] as $required) {
            if (! isset($this->tables[$required])) {
                throw new RuntimeException("Font is missing the [{$required}] table.");
            }
        }
    }

    private function table(string $tag): string
    {
        $t = $this->tables[$tag] ?? null;
        if ($t === null) {
            throw new RuntimeException("Font is missing the [{$tag}] table.");
        }

        return substr($this->data, $t['offset'], $t['length']);
    }

    private function optionalTable(string $tag): ?string
    {
        return isset($this->tables[$tag]) ? $this->table($tag) : null;
    }

    private function readPostScriptName(): string
    {
        if (! isset($this->tables['name'])) {
            return 'Font';
        }

        $name = $this->table('name');
        $count = $this->u16($name, 2);
        $stringOffset = $this->u16($name, 4);
        for ($i = 0; $i < $count; $i++) {
            $rec = 6 + $i * 12;
            $platform = $this->u16($name, $rec);
            $nameId = $this->u16($name, $rec + 6);
            if ($nameId !== 6) {
                continue;
            }
            $length = $this->u16($name, $rec + 8);
            $offset = $this->u16($name, $rec + 10);
            $raw = substr($name, $stringOffset + $offset, $length);
            $text = $platform === 3 || $platform === 0
                ? (mb_convert_encoding($raw, 'UTF-8', 'UTF-16BE') ?: '')
                : $raw;

            return preg_replace('/[^A-Za-z0-9-]/', '', $text) ?: 'Font';
        }

        return 'Font';
    }

    /** @return array<int, int> */
    private function readCmap(): array
    {
        $cmap = $this->table('cmap');
        $numSubtables = $this->u16($cmap, 2);

        $best = null;
        for ($i = 0; $i < $numSubtables; $i++) {
            $rec = 4 + $i * 8;
            $platform = $this->u16($cmap, $rec);
            $encoding = $this->u16($cmap, $rec + 2);
            $offset = $this->u32($cmap, $rec + 4);
            $format = $this->u16($cmap, $offset);

            // Prefer full Unicode (format 12), then BMP (format 4).
            if (($platform === 3 && $encoding === 10 && $format === 12) || ($platform === 0 && $format === 12)) {
                return $this->readCmapFormat12($cmap, $offset);
            }
            if ($format === 4 && ($platform === 3 || $platform === 0) && $best === null) {
                $best = $offset;
            }
        }

        if ($best === null) {
            throw new RuntimeException('Font has no Unicode cmap subtable.');
        }

        return $this->readCmapFormat4($cmap, $best);
    }

    /** @return array<int, int> */
    private function readCmapFormat4(string $cmap, int $offset): array
    {
        $segCount = $this->u16($cmap, $offset + 6) >> 1;
        $endCodes = $offset + 14;
        $startCodes = $endCodes + 2 * $segCount + 2;
        $idDeltas = $startCodes + 2 * $segCount;
        $idRangeOffsets = $idDeltas + 2 * $segCount;

        $map = [];
        for ($seg = 0; $seg < $segCount; $seg++) {
            $end = $this->u16($cmap, $endCodes + 2 * $seg);
            $start = $this->u16($cmap, $startCodes + 2 * $seg);
            $delta = $this->u16($cmap, $idDeltas + 2 * $seg);
            $rangeOffsetPos = $idRangeOffsets + 2 * $seg;
            $rangeOffset = $this->u16($cmap, $rangeOffsetPos);

            for ($c = $start; $c <= $end; $c++) {
                if ($c === 0xFFFF) {
                    continue;
                }
                if ($rangeOffset === 0) {
                    $gid = ($c + $delta) & 0xFFFF;
                } else {
                    $glyphPos = $rangeOffsetPos + $rangeOffset + 2 * ($c - $start);
                    $gid = $this->u16($cmap, $glyphPos);
                    if ($gid !== 0) {
                        $gid = ($gid + $delta) & 0xFFFF;
                    }
                }
                if ($gid !== 0) {
                    $map[$c] = $gid;
                }
            }
        }

        return $map;
    }

    /** @return array<int, int> */
    private function readCmapFormat12(string $cmap, int $offset): array
    {
        $groups = $this->u32($cmap, $offset + 12);
        $map = [];
        for ($g = 0; $g < $groups; $g++) {
            $rec = $offset + 16 + $g * 12;
            $start = $this->u32($cmap, $rec);
            $end = $this->u32($cmap, $rec + 4);
            $startGid = $this->u32($cmap, $rec + 8);
            for ($c = $start; $c <= $end; $c++) {
                $map[$c] = $startGid + ($c - $start);
            }
        }

        return $map;
    }

    private function u16(string $bytes, int $offset): int
    {
        return unpack('n', substr($bytes, $offset, 2))[1];
    }

    private function s16(string $bytes, int $offset): int
    {
        $v = $this->u16($bytes, $offset);

        return $v >= 0x8000 ? $v - 0x10000 : $v;
    }

    private function u32(string $bytes, int $offset): int
    {
        return unpack('N', substr($bytes, $offset, 4))[1];
    }
}
