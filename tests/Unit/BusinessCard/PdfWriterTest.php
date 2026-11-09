<?php

namespace Tests\Unit\BusinessCard;

use App\Services\BusinessCard\Pdf\PdfDocument;
use App\Services\BusinessCard\Pdf\PdfPreflight;
use App\Services\BusinessCard\Pdf\TrueTypeFont;
use PHPUnit\Framework\TestCase;

class PdfWriterTest extends TestCase
{
    private const FONT_DIR = __DIR__ . '/../../../resources/fonts/business-card';

    private function document(): PdfDocument
    {
        return new PdfDocument('Test card', 'CHRSD', 'Writer test', 'PdfWriterTest');
    }

    private function sans(): TrueTypeFont
    {
        return TrueTypeFont::fromFile(self::FONT_DIR . '/Montserrat-SemiBold.ttf');
    }

    public function test_output_has_boxes_separations_and_embedded_font_and_passes_preflight(): void
    {
        $doc = $this->document();
        $page = $doc->addPage(306, 198);
        $page->setBoxes([27, 27, 279, 171], [18, 18, 288, 180]);

        $foil = $doc->separation('Silver_Foil', [0.10, 0.06, 0.06, 0.30]);
        $reg = $doc->separation('All', [1, 1, 1, 1]);
        $sans = $this->sans();

        $page->save();
        $page->fillCmyk([0.88, 0.52, 0.86, 0.68]);
        $page->rect(18, 18, 270, 162);
        $page->fill();
        $page->restore();

        $page->save();
        $page->useState($doc->overprintState(true));
        $page->fillSpot($foil, 1.0);
        $page->rect(60, 100, 200, 0.75);
        $page->fill();
        $page->restore();

        $page->save();
        $page->strokeSpot($reg, 1.0);
        $page->lineWidth(0.25);
        $page->moveTo(4.5, 18);
        $page->lineTo(9, 18);
        $page->stroke();
        $page->restore();

        $page->save();
        $page->fillCmyk([0, 0, 0, 0]);
        $page->text($sans, 'Mohammed Abu Ramim', 12.48, 60, 120, 0.005);
        $page->restore();

        $pdf = $doc->output();
        $report = PdfPreflight::inspect($pdf);

        $this->assertTrue($report['ok'], implode('; ', $report['errors']));
        $this->assertSame(1, $report['pages']);
        $this->assertSame(0, $report['rgbOperators']);
        $this->assertContains('Silver_Foil', $report['separations']);
        $this->assertContains('All', $report['separations']);
        $this->assertNotEmpty($report['fonts']);
        $this->assertStringContainsString('/TrimBox [27 27 279 171]', $pdf);
        $this->assertStringContainsString('/BleedBox [18 18 288 180]', $pdf);
        $this->assertStringContainsString('/MediaBox [0 0 306 198]', $pdf);
    }

    public function test_output_is_deterministic_for_the_same_input(): void
    {
        $build = function (): string {
            $doc = $this->document();
            $page = $doc->addPage(306, 198);
            $page->fillCmyk([0.1, 0.2, 0.3, 0.4]);
            $page->rect(10, 10, 20, 20);
            $page->fill();

            return $doc->output();
        };

        $this->assertSame($build(), $build());
    }

    public function test_cross_reference_offsets_point_at_their_objects(): void
    {
        $doc = $this->document();
        $page = $doc->addPage(306, 198);
        $page->rect(0, 0, 10, 10);
        $page->fill();
        $pdf = $doc->output();

        $this->assertMatchesRegularExpression('/startxref\s+\d+\s+%%EOF\s*$/', $pdf);
        $report = PdfPreflight::inspect($pdf, []);
        $this->assertTrue($report['ok'], implode('; ', $report['errors']));
    }

    /**
     * A hand-built, uncompressed PDF so the content stream is readable to the
     * preflight. The writer always compresses, so this is the only way to feed
     * it a deliberately wrong operator.
     */
    private function uncompressedPdf(string $content): string
    {
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 306 198] /TrimBox [27 27 279 171] /BleedBox [18 18 288 180] /Contents 4 0 R >>',
            4 => '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "\nendstream",
        ];

        $pdf = "%PDF-1.7\n";
        $offsets = [];
        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= "{$id} 0 obj\n{$body}\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 5\n0000000000 65535 f \n";
        foreach (range(1, 4) as $id) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$id]);
        }
        $pdf .= "trailer\n<< /Size 5 /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";

        return $pdf;
    }

    public function test_preflight_flags_rgb_operators(): void
    {
        $pdf = $this->uncompressedPdf("0 0 0 1 k\n0 0 10 10 re f\n1 0 0 rg\n0 0 10 10 re f\n");

        $report = PdfPreflight::inspect($pdf, []);

        $this->assertFalse($report['ok']);
        $this->assertSame(1, $report['rgbOperators']);
        $this->assertSame(1, $report['cmykOperators']);
    }

    public function test_preflight_accepts_a_cmyk_only_stream(): void
    {
        $pdf = $this->uncompressedPdf("0 0 0 1 k\n0 0 10 10 re f\n0.1 0.2 0.3 0.4 K\n");

        $report = PdfPreflight::inspect($pdf, []);

        $this->assertSame(0, $report['rgbOperators']);
        $this->assertSame(2, $report['cmykOperators']);
    }

    public function test_preflight_flags_missing_required_separation(): void
    {
        $doc = $this->document();
        $page = $doc->addPage(306, 198);
        $page->setBoxes([27, 27, 279, 171], [18, 18, 288, 180]);
        $pdf = $doc->output();

        $report = PdfPreflight::inspect($pdf, ['Silver_Foil']);

        $this->assertFalse($report['ok']);
        $this->assertStringContainsString('Separation [Silver_Foil] is missing', implode(' ', $report['errors']));
    }

    public function test_preflight_flags_a_broken_xref(): void
    {
        $doc = $this->document();
        $page = $doc->addPage(306, 198);
        $page->setBoxes([27, 27, 279, 171], [18, 18, 288, 180]);
        $pdf = $doc->output();
        // Shift every xref offset by one byte.
        $pdf = preg_replace_callback('/(\d{10}) 00000 n/', fn ($m) => sprintf('%010d 00000 n', (int) $m[1] + 1), $pdf);

        $report = PdfPreflight::inspect($pdf, []);

        $this->assertFalse($report['ok']);
    }

    public function test_subset_font_parses_again_and_keeps_only_used_glyphs(): void
    {
        $sans = $this->sans();
        $sans->encode('Ramim');
        $subset = $sans->subset();

        // The subset is a valid sfnt: it parses again, with the glyphs we asked for.
        $reparsed = new TrueTypeFont($subset['bytes']);
        $this->assertSame($subset['length'], strlen($subset['bytes']));
        $this->assertLessThan(filesize(self::FONT_DIR . '/Montserrat-SemiBold.ttf') / 10, $subset['length']);
        $this->assertSame('Montserrat', substr($subset['name'], 7, 10));
        $this->assertNotSame('', $reparsed->postScriptName());
    }

    public function test_measure_matches_the_expected_advance_for_a_known_string(): void
    {
        $sans = $this->sans();

        $one = $sans->measure('A', 10.0);
        $two = $sans->measure('AA', 10.0);

        $this->assertGreaterThan(0.0, $one);
        $this->assertEqualsWithDelta(2 * $one, $two, 0.0001);
        // Tracking adds a fixed amount per glyph (Tc applies after every glyph).
        $this->assertEqualsWithDelta($two + 2 * 0.14 * 10.0, $sans->measure('AA', 10.0, 0.14), 0.0001);
    }

    public function test_missing_glyph_is_reported_not_silently_replaced(): void
    {
        $sans = $this->sans();

        $this->assertFalse($sans->covers("\u{1F600}"));
        $this->expectException(\InvalidArgumentException::class);
        $sans->encode("\u{1F600}");
    }

    public function test_shading_and_spot_colour_resources_are_declared(): void
    {
        $doc = $this->document();
        $page = $doc->addPage(306, 198);
        $page->setBoxes([27, 27, 279, 171], [18, 18, 288, 180]);
        $sh = $doc->axialShading([27, 0, 279, 0], [
            [0.0, 0.93, 0.45, 1.0, 0.50],
            [0.5, 0.84, 0.14, 0.92, 0.08],
            [1.0, 0.75, 0.05, 0.85, 0.00],
        ]);
        $page->save();
        $page->rect(27, 130, 252, 20);
        $page->clip();
        $page->shade($sh);
        $page->restore();

        $pdf = $doc->output();

        $this->assertStringContainsString('/ShadingType 2', $pdf);
        $this->assertStringContainsString('/FunctionType 3', $pdf);
        $this->assertStringContainsString('/DeviceCMYK', $pdf);
        $this->assertTrue(PdfPreflight::inspect($pdf, [])['ok']);
    }
}
