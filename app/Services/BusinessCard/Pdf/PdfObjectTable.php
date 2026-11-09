<?php

namespace App\Services\BusinessCard\Pdf;

use RuntimeException;

/**
 * Numbered PDF indirect objects and the cross-reference table.
 *
 * Objects are reserved first, filled in later, and serialised in ascending
 * number order, so every reserved object must be given a body before output.
 */
final class PdfObjectTable
{
    private int $next = 1;

    /** @var array<int, string|array{dict: string, data: string}> */
    private array $bodies = [];

    public function reserve(): int
    {
        return $this->next++;
    }

    public function set(int $id, string $body): void
    {
        $this->bodies[$id] = $body;
    }

    /**
     * Stream object. $extraDict holds entries such as "/Length1 1234" and is
     * merged into the stream dictionary. Compressed streams use Flate.
     */
    public function setStream(int $id, string $extraDict, string $data, bool $compress = false): void
    {
        $filter = '';
        if ($compress) {
            $data = gzcompress($data, 9);
            $filter = '/Filter /FlateDecode ';
        }

        $dict = '<< ' . ($extraDict !== '' ? $extraDict . ' ' : '') . $filter . '/Length ' . strlen($data) . ' >>';
        $this->bodies[$id] = ['dict' => $dict, 'data' => $data];
    }

    public function serialise(int $catalogId, int $infoId, string $idSeed): string
    {
        $count = $this->next;
        $out = "%PDF-1.7\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];

        for ($id = 1; $id < $count; $id++) {
            if (! array_key_exists($id, $this->bodies)) {
                throw new RuntimeException("PDF object {$id} was reserved but never written.");
            }

            $offsets[$id] = strlen($out);
            $body = $this->bodies[$id];
            if (is_array($body)) {
                $out .= "{$id} 0 obj\n{$body['dict']}\nstream\n{$body['data']}\nendstream\nendobj\n";
            } else {
                $out .= "{$id} 0 obj\n{$body}\nendobj\n";
            }
        }

        $xrefOffset = strlen($out);
        $out .= "xref\n0 {$count}\n0000000000 65535 f \n";
        for ($id = 1; $id < $count; $id++) {
            $out .= sprintf("%010d 00000 n \n", $offsets[$id]);
        }

        $fileId = strtoupper(md5($idSeed));
        $out .= "trailer\n<< /Size {$count} /Root {$catalogId} 0 R /Info {$infoId} 0 R /ID [<{$fileId}> <{$fileId}>] >>\n";
        $out .= "startxref\n{$xrefOffset}\n%%EOF\n";

        return $out;
    }
}
