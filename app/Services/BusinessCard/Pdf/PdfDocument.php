<?php

namespace App\Services\BusinessCard\Pdf;

use InvalidArgumentException;

/**
 * Minimal, deterministic PDF 1.7 writer for print artwork.
 *
 * Supports exactly what the business card needs: pages with TrimBox, BleedBox,
 * ArtBox and MediaBox; DeviceCMYK; named Separation colours with a CMYK
 * alternate; ExtGState overprint; axial CMYK shadings; embedded TrueType
 * (CIDFontType2, Identity-H); Flate-compressed streams. Output is byte-for-byte
 * reproducible for the same input.
 */
final class PdfDocument
{
    /** @var list<PdfPage> */
    private array $pages = [];

    /** @var array<string, array{0: float, 1: float, 2: float, 3: float}> separation name => CMYK alternate */
    private array $separations = [];

    /** @var array<string, TrueTypeFont> resource name (without slash) => font */
    private array $fonts = [];

    /** @var list<array{coords: list<float>, stops: list<array{0: float, 1: float, 2: float, 3: float, 4: float}>}> */
    private array $shadings = [];

    public function __construct(
        private readonly string $title,
        private readonly string $author,
        private readonly string $subject,
        private readonly string $creator,
        private readonly string $creationDate = 'D:20260101000000Z',
    ) {}

    public function addPage(float $widthPt, float $heightPt): PdfPage
    {
        $page = new PdfPage($this, $widthPt, $heightPt);
        $this->pages[] = $page;

        return $page;
    }

    /**
     * Register (or reuse) a named Separation colour with a CMYK alternate (0-100 each).
     * Returns the resource name to use with cs/scn, e.g. "CS1".
     *
     * @param  array{0: float, 1: float, 2: float, 3: float}  $alternateCmyk  0.0-1.0 per channel
     */
    public function separation(string $name, array $alternateCmyk): string
    {
        if (! preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $name)) {
            throw new InvalidArgumentException("Invalid separation name [{$name}].");
        }

        $this->separations[$name] ??= $alternateCmyk;

        return 'CS' . (array_search($name, array_keys($this->separations), true) + 1);
    }

    /** Overprint on (fill and stroke) or off. Returns the ExtGState resource name. */
    public function overprintState(bool $on): string
    {
        return $on ? 'GSOP' : 'GSNOP';
    }

    /** Register a TrueType font; returns its resource name (e.g. "F1"). */
    public function font(TrueTypeFont $font): string
    {
        foreach ($this->fonts as $name => $existing) {
            if ($existing === $font) {
                return $name;
            }
        }

        $name = 'F' . (count($this->fonts) + 1);
        $this->fonts[$name] = $font;

        return $name;
    }

    /**
     * Axial (linear) shading in DeviceCMYK. Stops are [position 0..1, c, m, y, k]
     * with channels 0..1. Returns the shading resource name (e.g. "SH1").
     *
     * @param  list<float>  $coords  x0, y0, x1, y1 in points
     * @param  list<array{0: float, 1: float, 2: float, 3: float, 4: float}>  $stops
     */
    public function axialShading(array $coords, array $stops): string
    {
        if (count($coords) !== 4 || count($stops) < 2) {
            throw new InvalidArgumentException('An axial shading needs four coordinates and at least two stops.');
        }

        $this->shadings[] = ['coords' => $coords, 'stops' => $stops];

        return 'SH' . count($this->shadings);
    }

    public function pageCount(): int
    {
        return count($this->pages);
    }

    /** @return list<PdfPage> */
    public function pages(): array
    {
        return $this->pages;
    }

    /** @return array<string, array{0: float, 1: float, 2: float, 3: float}> separation name => CMYK alternate */
    public function separationAlternates(): array
    {
        return $this->separations;
    }

    /**
     * Serialise the whole document. Object numbers are assigned in a fixed order,
     * so the same input always yields the same bytes.
     */
    public function output(): string
    {
        $builder = new PdfObjectTable;

        $catalogId = $builder->reserve();
        $pagesId = $builder->reserve();
        $infoId = $builder->reserve();
        $resourcesId = $builder->reserve();

        // Shared resources: colour spaces, overprint states, fonts, shadings.
        $csEntries = [];
        $i = 0;
        foreach ($this->separations as $name => $alt) {
            $i++;
            $csEntries[] = "/CS{$i} [/Separation /{$name} /DeviceCMYK << /FunctionType 2 /Domain [0 1] /C0 [0 0 0 0] /C1 ["
                . $this->cmykList($alt) . '] /N 1 >>]';
        }
        $csDict = $csEntries === [] ? '' : '/ColorSpace << ' . implode(' ', $csEntries) . ' >> ';

        $gsDict = '/ExtGState << /GSOP << /Type /ExtGState /OP true /op true /OPM 1 >> '
            . '/GSNOP << /Type /ExtGState /OP false /op false /OPM 0 >> >> ';

        $fontDict = '';
        $fontRefs = [];
        foreach ($this->fonts as $fontName => $font) {
            $refs = $this->writeFont($builder, $font);
            $fontRefs[] = "/{$fontName} {$refs['type0']} 0 R";
        }
        if ($fontRefs !== []) {
            $fontDict = '/Font << ' . implode(' ', $fontRefs) . ' >> ';
        }

        $shDict = '';
        if ($this->shadings !== []) {
            $shEntries = [];
            foreach ($this->shadings as $k => $shading) {
                $shId = $this->writeShading($builder, $shading);
                $shEntries[] = '/SH' . ($k + 1) . " {$shId} 0 R";
            }
            $shDict = '/Shading << ' . implode(' ', $shEntries) . ' >> ';
        }

        $builder->set($resourcesId, "<< {$csDict}{$gsDict}{$fontDict}{$shDict}>>");

        // Pages and content streams.
        $pageIds = [];
        foreach ($this->pages as $page) {
            $contentId = $builder->reserve();
            $contentStream = $page->content();
            $builder->setStream($contentId, '', $contentStream);

            $pageId = $builder->reserve();
            $pageIds[] = $pageId;
            $builder->set($pageId, sprintf(
                '<< /Type /Page /Parent %d 0 R /MediaBox %s /TrimBox %s /BleedBox %s /ArtBox %s /Resources %d 0 R /Contents %d 0 R >>',
                $pagesId,
                $this->box($page->mediaBox()),
                $this->box($page->trimBox()),
                $this->box($page->bleedBox()),
                $this->box($page->artBox()),
                $resourcesId,
                $contentId,
            ));
        }

        $kids = implode(' ', array_map(fn (int $id) => "{$id} 0 R", $pageIds));
        $builder->set($pagesId, "<< /Type /Pages /Kids [{$kids}] /Count " . count($pageIds) . ' >>');
        $builder->set($catalogId, "<< /Type /Catalog /Pages {$pagesId} 0 R >>");
        $builder->set($infoId, sprintf(
            '<< /Title %s /Author %s /Subject %s /Creator %s /Producer %s /CreationDate (%s) >>',
            $this->pdfString($this->title),
            $this->pdfString($this->author),
            $this->pdfString($this->subject),
            $this->pdfString($this->creator),
            $this->pdfString('CHRSD card PDF writer (pure PHP)'),
            $this->creationDate,
        ));

        return $builder->serialise($catalogId, $infoId, $this->fileIdSeed());
    }

    /**
     * @param  array{0: float, 1: float, 2: float, 3: float}  $cmyk
     */
    private function cmykList(array $cmyk): string
    {
        return implode(' ', array_map(fn (float $v) => $this->num($v), $cmyk));
    }

    /**
     * @return array{type0: int}
     */
    private function writeFont(PdfObjectTable $builder, TrueTypeFont $font): array
    {
        $subset = $font->subset();

        $fileId = $builder->reserve();
        $builder->setStream($fileId, "/Length1 {$subset['length']}", $subset['bytes'], compress: true);

        $descId = $builder->reserve();
        $bbox = $font->bbox();
        $builder->set($descId, sprintf(
            '<< /Type /FontDescriptor /FontName /%s /Flags 32 /FontBBox [%s] /ItalicAngle 0 /Ascent %d /Descent %d /CapHeight %d /StemV 80 /FontFile2 %d 0 R >>',
            $subset['name'],
            implode(' ', $bbox),
            $font->ascent(),
            $font->descent(),
            $font->capHeight(),
            $fileId,
        ));

        $cidId = $builder->reserve();
        $builder->set($cidId, sprintf(
            '<< /Type /Font /Subtype /CIDFontType2 /BaseFont /%s /CIDSystemInfo << /Registry (Adobe) /Ordering (Identity) /Supplement 0 >> /FontDescriptor %d 0 R /DW 1000 /W [%s] /CIDToGIDMap /Identity >>',
            $subset['name'],
            $descId,
            implode(' ', $subset['widths']),
        ));

        $toUnicodeId = $builder->reserve();
        $builder->setStream($toUnicodeId, '', $subset['toUnicode'], compress: true);

        $type0Id = $builder->reserve();
        $builder->set($type0Id, sprintf(
            '<< /Type /Font /Subtype /Type0 /BaseFont /%s /Encoding /Identity-H /DescendantFonts [%d 0 R] /ToUnicode %d 0 R >>',
            $subset['name'],
            $cidId,
            $toUnicodeId,
        ));

        return ['type0' => $type0Id];
    }

    /**
     * @param  array{coords: list<float>, stops: list<array{0: float, 1: float, 2: float, 3: float, 4: float}>}  $shading
     */
    private function writeShading(PdfObjectTable $builder, array $shading): int
    {
        $stops = $shading['stops'];
        $bounds = [];
        $functionIds = [];

        // One Type 2 exponential function per adjacent pair of stops, joined by a Type 3 stitching function.
        for ($i = 0; $i < count($stops) - 1; $i++) {
            $fid = $builder->reserve();
            $builder->set($fid, sprintf(
                '<< /FunctionType 2 /Domain [0 1] /C0 [%s] /C1 [%s] /N 1 >>',
                $this->cmykList(array_slice($stops[$i], 1)),
                $this->cmykList(array_slice($stops[$i + 1], 1)),
            ));
            $functionIds[] = "{$fid} 0 R";
            if ($i > 0) {
                $bounds[] = $this->num($stops[$i][0]);
            }
        }

        $stitchId = $builder->reserve();
        $encode = implode(' ', array_fill(0, count($functionIds), '0 1'));
        $builder->set($stitchId, sprintf(
            '<< /FunctionType 3 /Domain [0 1] /Functions [%s] /Bounds [%s] /Encode [%s] >>',
            implode(' ', $functionIds),
            implode(' ', $bounds),
            $encode,
        ));

        $shId = $builder->reserve();
        $builder->set($shId, sprintf(
            '<< /ShadingType 2 /ColorSpace /DeviceCMYK /Coords [%s] /Function %d 0 R /Extend [true true] >>',
            implode(' ', array_map(fn (float $v) => $this->num($v), $shading['coords'])),
            $stitchId,
        ));

        return $shId;
    }

    /** @param  array{0: float, 1: float, 2: float, 3: float}  $box */
    private function box(array $box): string
    {
        return '[' . implode(' ', array_map(fn (float $v) => $this->num($v), $box)) . ']';
    }

    public function num(float $value): string
    {
        // Fixed precision, no exponent, trailing zeros removed: deterministic and valid PDF syntax.
        $s = rtrim(rtrim(sprintf('%.4F', $value), '0'), '.');

        return $s === '' || $s === '-0' ? '0' : $s;
    }

    public function pdfString(string $text): string
    {
        // ASCII-only metadata in literal form, escaped per PDF 7.3.4.2.
        $escaped = str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '\\r', '\\n'], $text);

        return '(' . $escaped . ')';
    }

    private function fileIdSeed(): string
    {
        return md5($this->title . '|' . $this->author . '|' . $this->subject . '|' . $this->creationDate);
    }
}
