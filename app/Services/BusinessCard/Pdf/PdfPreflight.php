<?php

namespace App\Services\BusinessCard\Pdf;

/**
 * Structural preflight for the card PDFs. Runs in pure PHP on every export and
 * on every test, so the checks do not depend on any external tool.
 *
 * It checks what the printer needs to trust the file: a well-formed xref,
 * boxes on every page, DeviceCMYK only (no RGB operators), the named spot and
 * registration colourants, and fonts that are embedded (FontFile2).
 *
 * It does not claim PDF/X compliance. That needs a real preflight tool.
 */
final class PdfPreflight
{
    /**
     * @return array{ok: bool, pages: int, errors: list<string>, warnings: list<string>,
     *   separations: list<string>, fonts: list<string>, rgbOperators: int, cmykOperators: int}
     */
    public static function inspect(string $pdf, array $requiredSeparations = ['Silver_Foil', 'All']): array
    {
        $errors = [];
        $warnings = [];

        $objects = self::objects($pdf, $errors);
        if ($objects === []) {
            return self::report(false, 0, $errors, $warnings, [], [], 0, 0);
        }

        // Pages and boxes.
        $pages = [];
        foreach ($objects as $id => $body) {
            if (preg_match('#/Type\s*/Page\b(?!s)#', $body['dict'] ?? $body['raw'])) {
                $pages[$id] = $body['dict'] ?? $body['raw'];
            }
        }
        if ($pages === []) {
            $errors[] = 'No pages found.';
        }
        foreach ($pages as $id => $dict) {
            foreach (['MediaBox', 'TrimBox', 'BleedBox'] as $box) {
                if (! preg_match('#/' . $box . '\s*\[#', $dict)) {
                    $errors[] = "Page object {$id} has no {$box}.";
                }
            }
        }

        // Colour operators in every content stream.
        $rgb = 0;
        $cmyk = 0;
        foreach ($objects as $body) {
            if (! isset($body['stream'])) {
                continue;
            }
            $content = $body['content'] ?? '';
            if ($content === '' || ! str_contains($body['dict'], 'Length')) {
                continue;
            }
            $rgb += preg_match_all('/(?<![\w\/])(rg|RG)(?=\s)/', $content);
            $cmyk += preg_match_all('/(?<![\w\/])(k|K)(?=\s)/', $content);
        }
        if ($rgb > 0) {
            $errors[] = "Found {$rgb} RGB colour operator(s); the artwork must be CMYK only.";
        }
        foreach ($objects as $body) {
            if (isset($body['dict']) && str_contains($body['dict'], 'DeviceRGB')) {
                $errors[] = 'A resource or colour space uses DeviceRGB.';
                break;
            }
        }

        // Separations present in the resources.
        $separations = [];
        foreach ($objects as $body) {
            if (preg_match_all('#/Separation\s*/([A-Za-z0-9_]+)#', $body['raw'] ?? '', $m)) {
                foreach ($m[1] as $name) {
                    $separations[$name] = true;
                }
            }
        }
        $separations = array_keys($separations);
        foreach ($requiredSeparations as $required) {
            if (! in_array($required, $separations, true)) {
                $errors[] = "Separation [{$required}] is missing.";
            }
        }

        // Fonts: every font must be embedded through FontFile2.
        $fonts = [];
        foreach ($objects as $id => $body) {
            $raw = $body['raw'] ?? '';
            if (preg_match('#/Type\s*/FontDescriptor#', $raw)) {
                $name = preg_match('#/FontName\s*/([^\s/>\[]+)#', $raw, $n) ? $n[1] : "obj{$id}";
                $fonts[] = $name;
                if (! preg_match('#/FontFile2\s+\d+\s+0\s+R#', $raw)) {
                    $errors[] = "Font [{$name}] is not embedded (no FontFile2).";
                }
            }
        }

        return self::report($errors === [], count($pages), $errors, $warnings, $separations, $fonts, $rgb, $cmyk);
    }

    /**
     * Parse indirect objects by following the xref offsets. Each entry holds the
     * raw body, the dictionary text, and for streams the decoded content.
     *
     * @param  list<string>  $errors
     * @return array<int, array{raw: string, dict?: string, stream?: bool, content?: string}>
     */
    private static function objects(string $pdf, array &$errors): array
    {
        if (! str_starts_with($pdf, '%PDF-')) {
            $errors[] = 'Missing %PDF header.';

            return [];
        }

        if (! preg_match('/startxref\s+(\d+)\s+%%EOF\s*$/', $pdf, $m)) {
            $errors[] = 'Missing startxref or %%EOF at the end of the file.';

            return [];
        }

        $xrefOffset = (int) $m[1];
        if (substr($pdf, $xrefOffset, 4) !== 'xref') {
            $errors[] = 'startxref does not point at an xref table.';

            return [];
        }

        if (! preg_match('/^xref\s+0\s+(\d+)\s*\n/m', substr($pdf, $xrefOffset), $h)) {
            $errors[] = 'xref table header is malformed.';

            return [];
        }
        $count = (int) $h[1];

        $entries = preg_match_all('/(\d{10}) (\d{5}) (n|f) ?\r?\n/', substr($pdf, $xrefOffset), $e, PREG_SET_ORDER);
        if ($entries !== $count) {
            $errors[] = "xref lists {$entries} entries but declares {$count}.";
        }

        $objects = [];
        foreach ($e as $index => $entry) {
            $id = $index;
            if ($entry[3] !== 'n' || $id === 0) {
                continue;
            }

            $offset = (int) $entry[1];
            $header = sprintf('%d 0 obj', $id);
            if (substr($pdf, $offset, strlen($header)) !== $header) {
                $errors[] = "xref offset for object {$id} does not point at its definition.";

                continue;
            }

            $end = strpos($pdf, 'endobj', $offset);
            if ($end === false) {
                $errors[] = "Object {$id} is not terminated by endobj.";

                continue;
            }

            $body = substr($pdf, $offset + strlen($header), $end - $offset - strlen($header));
            $objects[$id] = ['raw' => $body];

            if (preg_match('/stream\r?\n/', $body, $sm, PREG_OFFSET_CAPTURE)) {
                $dict = substr($body, 0, $sm[0][1]);
                $dataStart = $sm[0][1] + strlen($sm[0][0]);
                $length = preg_match('#/Length\s+(\d+)#', $dict, $lm) ? (int) $lm[1] : null;
                $data = $length === null ? '' : substr($body, $dataStart, $length);
                if ($length !== null && str_contains($dict, 'FlateDecode')) {
                    $inflated = @gzuncompress($data);
                    $data = $inflated === false ? '' : $inflated;
                    if ($inflated === false) {
                        $errors[] = "Stream in object {$id} cannot be inflated.";
                    }
                }
                $objects[$id]['dict'] = $dict;
                $objects[$id]['stream'] = true;
                $objects[$id]['content'] = $data;
            } else {
                $objects[$id]['dict'] = $body;
            }
        }

        return $objects;
    }

    /**
     * @param  list<string>  $errors
     * @param  list<string>  $warnings
     * @param  list<string>  $separations
     * @param  list<string>  $fonts
     * @return array{ok: bool, pages: int, errors: list<string>, warnings: list<string>,
     *   separations: list<string>, fonts: list<string>, rgbOperators: int, cmykOperators: int}
     */
    private static function report(bool $ok, int $pages, array $errors, array $warnings, array $separations, array $fonts, int $rgb, int $cmyk): array
    {
        return [
            'ok' => $ok,
            'pages' => $pages,
            'errors' => $errors,
            'warnings' => $warnings,
            'separations' => $separations,
            'fonts' => array_values(array_unique($fonts)),
            'rgbOperators' => $rgb,
            'cmykOperators' => $cmyk,
        ];
    }
}
