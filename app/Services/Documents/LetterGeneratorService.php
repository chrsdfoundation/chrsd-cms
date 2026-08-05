<?php

namespace App\Services\Documents;

use App\Enums\OfficialLetterStatus;
use App\Models\OfficialLetter;
use App\Services\Verification\QrCodeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Config;

class LetterGeneratorService
{
    public function __construct(
        protected QrCodeService $qr,
        protected PdfSignatureService $signer,
        protected TemplateRenderer $templates,
        protected MpdfPdfService $mpdf,
        protected BrowsershotPdfService $browsershot,
    ) {}

    public function generate(OfficialLetter $letter): OfficialLetter
    {
        return DB::transaction(function () use ($letter) {
            $letter->loadMissing(['category', 'letterAuthor', 'author.department', 'author.position', 'signedBy', 'documentTemplate']);

            $pdfBytes = $this->renderPdf($letter);

            $signature = $this->signer->sign($pdfBytes);

            $letter
                ->addMediaFromString($pdfBytes)
                ->usingFileName(sprintf('%s.pdf', $letter->serial_number))
                ->usingName($letter->serial_number)
                ->toMediaCollection('rendered');

            $letter->forceFill([
                'letter_status' => OfficialLetterStatus::Released,
                'released_on' => $letter->released_on ?? now()->toDateString(),
                'pdf_content_hash' => $signature,
            ])->save();

            return $letter->refresh();
        });
    }

    /**
     * Render the letter to PDF bytes via mPDF.
     *
     * The mPDF letterhead strategy: @page margins define the safe text zone.
     * position:fixed elements with negative offsets anchor to the physical page
     * edge (0,0), so they repeat on every page without header/footer stitching.
     *
     * All image assets (letterhead, watermark, signatures) are passed as base64
     * data URIs so mPDF can render them directly without HTTP round-trips or
     * filesystem path resolution issues.
     */
    protected function renderPdf(OfficialLetter $letter): string
    {
        if ($letter->documentTemplate) {
            $html = $this->templates->render(
                $letter->documentTemplate,
                $this->buildContext($letter),
                'documents.templates.letter-shell'
            );
        } else {
            $html = View::make('documents.letters.default', [
                'letter' => $letter,
                'author' => $letter->author,
                'signatory' => $letter->signedBy,
                'category' => $letter->category,
                'qr_svg' => $this->qr->svg($letter, 4),
                'verify_url' => $this->qr->verificationUrl($letter),
                'body_html' => $this->transformBodyMarkup($letter->body ?? ''),
                'letterhead_uri' => $this->dataUriFor(public_path('images/brand/Letterhead-dompdf.png')),
            ])->render();
        }

        $opts = ['margin' => ['top' => '103mm', 'right' => '18mm', 'bottom' => '26mm', 'left' => '22mm']];

        return Config::get('browsershot.enabled')
            ? $this->browsershot->render($html, $opts)
            : $this->mpdf->render($html, $opts);
    }

    /** Read a file and return its base64 data URI, or null if it doesn't exist. */
    protected function dataUriFor(string $path): ?string
    {
        if (! is_file($path)) {
            return null;
        }
        $mime = mime_content_type($path) ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
    }

    /** Standard placeholder context for a letter. */
    public function buildContext(OfficialLetter $letter): array
    {
        // Prefer the new Author model; fall back to legacy Employee author.
        $letterAuthor = $letter->letterAuthor;
        $employee = $letter->author;
        $signatory = $letter->signedBy;

        $authorName = $letterAuthor?->name ?? $employee?->full_name ?? '';
        $authorTitle = $letterAuthor?->designation ?? optional($employee?->position)->title ?? '';
        $authorOrg = $letterAuthor?->organization ?? config('app.name');

        // Signatory falls back to letter author.
        $signatoryName = $signatory?->full_name ?? $authorName;
        $signatoryTitle = optional($signatory?->position)->title ?? $authorTitle;

        // Prefer SVG for the template-shell QR: Chromium renders the vector
        // crisply at any print size, whereas the palette PNG produced by
        // Milon\Barcode is 1-bit indexed and can drop out of PDF exports.
        $qrImg = $this->qr->svg($letter, 4);

        // Digital signature image. Prefer a per-letter upload from Spatie
        // MediaLibrary; if none, try the signatory's brand-kit PNG (matches
        // the pattern CertificateGeneratorService uses).
        $signatureImg = $this->resolveSignatureImage($letter);

        return [
            'name' => $letter->recipient_name ?? '',
            'designation' => $letter->recipient_title ?? '',
            'organization' => optional($letter->organization)->name ?? $authorOrg,
            'certificate_number' => $letter->serial_number,
            'letter_reference' => $letter->serial_number,
            'date' => optional($letter->dated_on ?? $letter->created_at)->toFormattedDateString(),
            'issue_date' => optional($letter->dated_on ?? $letter->created_at)->toFormattedDateString(),
            'position' => $authorTitle,
            'subject' => $letter->subject ?? '',
            // Body is pipe-table-normalised here too so DB-template letters
            // (which don't go through the default-browsershot transform) still
            // get real <table> markup when authors type pipe rows.
            'body' => $this->transformBodyMarkup($letter->body ?? ''),
            'recipient_name' => $letter->recipient_name ?? '',
            'recipient_title' => $letter->recipient_title ?? '',
            'recipient_address' => $letter->recipient_address ?? '',
            'signatory_name' => $signatoryName,
            'signatory_title' => $signatoryTitle,
            'verification_url' => $this->qr->verificationUrl($letter),
            'qr_code' => $qrImg,
            'signature_image' => $signatureImg,
            'watermark_uri' => (string) $this->dataUriFor(public_path('images/brand/letterhead-watermark.png')),
            'letterhead_uri' => (string) $this->dataUriFor(public_path('images/brand/Letterhead-dompdf.png')),
        ];
    }

    /**
     * Return an <img> tag for the signatory's signature or an empty string.
     * Kept for the template-shell path (documents.templates.letter-shell-browsershot);
     * the default-browsershot template consumes the raw data URI via
     * resolveSignatureDataUri() instead.
     */
    protected function resolveSignatureImage(OfficialLetter $letter): string
    {
        $uri = $this->resolveSignatureDataUri($letter);
        if ($uri === '') {
            return '';
        }

        return sprintf(
            '<img src="%s" alt="Signature" style="display:block;max-height:70px;max-width:220px;margin-bottom:2px;object-fit:contain;" />',
            $uri,
        );
    }

    /**
     * Resolve the effective signature image as a data: URI (or empty string).
     * Priority: (1) per-letter upload in the 'signature' media collection,
     *           (2) brand-kit PNG matching the signatory's name-slug.
     */
    protected function resolveSignatureDataUri(OfficialLetter $letter): string
    {
        // 1. Per-letter upload
        if ($letter->hasMedia('signature')) {
            $path = $letter->getFirstMedia('signature')->getPath();
            if (is_file($path)) {
                $mime = mime_content_type($path) ?: 'image/png';

                return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
            }
        }

        // 2. Brand-kit fallback keyed on the signatory's name slug.
        $signatoryName = $letter->signedBy?->full_name ?? $letter->letterAuthor?->name ?? '';
        if ($signatoryName === '') {
            return '';
        }
        $slug = str($signatoryName)->slug()->value();
        $path = public_path("images/brand/signatures/{$slug}.png");
        if (is_file($path)) {
            return 'data:image/png;base64,' . base64_encode(file_get_contents($path));
        }

        return '';
    }

    /**
     * Prepare the rich-editor body for PDF rendering.
     *
     * Filament's RichEditor doesn't ship a "create table" button, so editors
     * type tabular data as pipe-delimited plain text (GFM-style). We detect
     * those blocks and convert them to real HTML <table> elements before the
     * PDF renderer sees them; existing HTML in the body is left intact.
     *
     * Recognised shapes:
     *   • rows separated by newlines, <br>, or <br/> — mix is fine
     *   • rows can be inside <p>…</p> or bare
     *   • leading &nbsp; before the pipe is tolerated
     *   • header separator row (| --- | --- |) is optional
     *   • if the first row has N × columns of the later rows (editor pasted
     *     header + first data row on one line), it is auto-split into N rows
     */
    protected function transformBodyMarkup(string $html): string
    {
        // Normalise <br> / <br/> to newlines and NBSP to space so a single
        // splitter handles rows regardless of how the RichEditor stored them.
        // Also inject line breaks around <p>/</p> so a pipe row ending "…
        // Athlete</p><p>Next paragraph…</p>" doesn't slurp the following
        // paragraph into the last cell.
        $normalised = preg_replace('#<br\s*/?>#i', "\n", $html) ?? $html;
        $normalised = preg_replace('#(</?p[^>]*>)#i', "\n$1\n", $normalised) ?? $normalised;
        $normalised = str_replace(["\xC2\xA0", '&nbsp;'], ' ', $normalised);

        $lines = preg_split('/\R/', $normalised);
        if ($lines === false || count($lines) < 2) {
            return $html;
        }

        $isPipeLine = static function (string $line): bool {
            // Strip a leading <p> and any leading whitespace before deciding.
            $stripped = preg_replace('#^\s*<p[^>]*>\s*#i', '', $line);
            $stripped = ltrim((string) $stripped);

            return $stripped !== '' && $stripped[0] === '|' && str_contains($stripped, '|');
        };

        // "Boundary" lines — pure <p>/</p> tags or blank space — are invisible
        // between pipe rows: the RichEditor wraps every paragraph (including a
        // single row of a table) in its own <p>, so a real pipe-table looks
        // like "<p>|a|b|</p><p>|c|d|</p>". Without this, each row would flush
        // on the </p> before another row could join it.
        $isBoundaryLine = static function (string $line): bool {
            $t = trim($line);

            return $t === '' || (bool) preg_match('#^</?p[^>]*>$#i', $t);
        };

        $output = [];
        $buffer = [];        // accumulated pipe rows
        $pending = [];       // boundary lines seen while inside a pipe run
        $flush = function () use (&$buffer, &$pending, &$output) {
            if (count($buffer) >= 2) {
                $output[] = $this->pipeBlockToTable(implode("\n", $buffer));
            } else {
                foreach ($buffer as $b) {
                    $output[] = $b;
                }
            }
            foreach ($pending as $p) {
                $output[] = $p;
            }
            $buffer = [];
            $pending = [];
        };

        foreach ($lines as $line) {
            if ($isPipeLine($line)) {
                // Boundaries buffered so far were between two pipe rows —
                // discard them; they were noise between table cells.
                $pending = [];
                $buffer[] = $line;

                continue;
            }
            if (! empty($buffer) && $isBoundaryLine($line)) {
                $pending[] = $line;

                continue;
            }
            $flush();
            $output[] = $line;
        }
        $flush();

        return implode("\n", $output);
    }

    /** Convert a matched pipe-delimited block into an HTML <table>. */
    private function pipeBlockToTable(string $block): string
    {
        $rows = [];
        foreach (preg_split('/\R+/', $block) as $line) {
            // Strip surrounding <p>…</p>, non-breaking space, and whitespace
            $line = preg_replace('#</?p[^>]*>#i', '', $line);
            $line = trim(str_replace(["\xC2\xA0"], ' ', $line));
            if ($line === '' || $line[0] !== '|') {
                continue;
            }
            $inner = trim($line, '|');
            $cells = array_map('trim', explode('|', $inner));
            // Strip inline formatting we don't need in a cell (bold on header
            // cells is applied via CSS; leave other <em>/<strong>/<a> intact).
            $cells = array_map(fn ($c) => preg_replace('#</?strong[^>]*>#i', '', $c), $cells);
            $rows[] = array_values(array_filter($cells, fn ($c) => $c !== ''));
        }
        if (count($rows) < 2) {
            return $block; // not enough rows to make a table
        }

        // Drop optional separator row of form | --- | --- |
        $header = array_shift($rows);
        if (! empty($rows) && $this->isSeparatorRow($rows[0])) {
            array_shift($rows);
        }
        $bodyRows = $rows;

        // Auto-split: if the header has 2x, 3x, … the column-count of a later
        // row, the editor pasted them together. Split into groups matching
        // the shorter row's width.
        if (! empty($bodyRows)) {
            $bodyCols = max(array_map('count', $bodyRows));
            if ($bodyCols > 0 && count($header) > $bodyCols && count($header) % $bodyCols === 0) {
                $chunks = array_chunk($header, $bodyCols);
                $header = array_shift($chunks);
                // Prepend the salvaged rows in their original order.
                $bodyRows = array_merge($chunks, $bodyRows);
            }
        }

        $out = '<table class="letter-body-table"><thead><tr>';
        foreach ($header as $h) {
            $out .= '<th>' . $h . '</th>';
        }
        $out .= '</tr></thead><tbody>';
        foreach ($bodyRows as $row) {
            $out .= '<tr>';
            foreach ($row as $cell) {
                $out .= '<td>' . $cell . '</td>';
            }
            $out .= '</tr>';
        }
        $out .= '</tbody></table>';

        return $out;
    }

    private function isSeparatorRow(array $cells): bool
    {
        foreach ($cells as $c) {
            if (! preg_match('/^:?-{2,}:?$/', trim($c))) {
                return false;
            }
        }

        return count($cells) > 0;
    }
}
