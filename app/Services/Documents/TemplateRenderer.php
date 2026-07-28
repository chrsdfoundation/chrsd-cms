<?php

namespace App\Services\Documents;

use App\Models\DocumentTemplate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

/**
 * Renders a DocumentTemplate's body_markdown into standalone HTML suitable for
 * DomPDF, substituting {{placeholder}} tokens with values from the context.
 *
 * Placeholder rules:
 *   - {{ key }}       — HTML-escaped substitution (safe by default)
 *   - {{{ key }}}     — raw substitution (used by us for pre-rendered SVG QR)
 *   - Missing keys resolve to empty string; missing keys are also collected and
 *     returned by lastMissing() so the UI can warn on preview.
 *
 * Standard context keys (populated by the caller — this service just substitutes):
 *   name, designation, organization, certificate_number, letter_reference,
 *   date, event_name, position, duration, verification_url, qr_code,
 *   subject, body, recipient_name, recipient_title, recipient_address, issue_date
 */
class TemplateRenderer
{
    /** Keys collected during the last render() call that were referenced but not provided. */
    protected array $missing = [];

    public function render(DocumentTemplate $template, array $context, ?string $shellOverride = null): string
    {
        $this->missing = [];
        $substituted = $this->substitute($template->body_markdown ?? '', $context);
        $inner = Str::markdown($substituted);

        return $this->wrap($template, $inner, $context, $shellOverride);
    }

    /** @return string[] Placeholder keys that appeared in the template but were not in the context. */
    public function lastMissing(): array
    {
        return array_values(array_unique($this->missing));
    }

    protected function substitute(string $source, array $context): string
    {
        // Triple-brace first: raw substitution (no HTML escape). Used for pre-rendered SVG QR.
        $source = preg_replace_callback('/\{\{\{\s*([a-zA-Z0-9_.-]+)\s*\}\}\}/', function ($m) use ($context) {
            return $this->lookup($m[1], $context, escape: false);
        }, $source) ?? $source;

        // Double-brace: safe (HTML-escaped) substitution.
        $source = preg_replace_callback('/\{\{\s*([a-zA-Z0-9_.-]+)\s*\}\}/', function ($m) use ($context) {
            return $this->lookup($m[1], $context, escape: true);
        }, $source) ?? $source;

        return $source;
    }

    protected function lookup(string $key, array $context, bool $escape): string
    {
        $value = data_get($context, $key);
        if ($value === null || $value === '') {
            $this->missing[] = $key;

            return '';
        }
        if (! $escape) {
            return (string) $value;
        }

        // Escape user content, then preserve newlines as <br> so multi-line
        // fields (recipient_address, body paragraphs) render correctly.
        return nl2br(e((string) $value), false);
    }

    protected function wrap(DocumentTemplate $template, string $bodyHtml, array $context, ?string $shellOverride = null): string
    {
        $orientation = $template->orientation === 'landscape' ? 'landscape' : 'portrait';

        if ($shellOverride !== null) {
            return View::make($shellOverride, [
                'bodyHtml' => $bodyHtml,
                'orientation' => $orientation,
                'letter_reference' => $context['letter_reference'] ?? '',
                'verification_url' => $context['verification_url'] ?? '',
                'qr_raw' => (string) ($context['qr_code'] ?? ''),
                'watermark_uri' => (string) ($context['watermark_uri'] ?? ''),
                'letterhead_uri' => (string) ($context['letterhead_uri'] ?? ''),
            ])->render();
        }

        $shell = match ((string) $template->document_type?->value) {
            'letter' => 'documents.templates.letter-shell',
            'id_card' => 'documents.templates.id-card-shell',
            default => 'documents.templates.certificate-shell',
        };

        // Certificate shell has a course-completion variant — different chrome
        // (blue/green waves, world-map watermark, QR-left, ornament divider).
        if ($template->document_type?->value === 'certificate'
            && in_array($template->shell_variant, ['course-completion', 'course-completion-blue'], true)) {
            $shell = 'documents.templates.certificate-course-shell';
        }

        // USAID/GlobalHealth-style shell: cream double-border, seal watermark,
        // three-column signature row, USAID footer. Matches
        // resources/views/certificates/course_completion.blade.php.
        if ($template->document_type?->value === 'certificate'
            && $template->shell_variant === 'course-completion-usaid') {
            $shell = 'documents.templates.certificate-usaid-shell';
        }

        return View::make($shell, [
            'bodyHtml' => $bodyHtml,
            'orientation' => $orientation,
            'template' => $template,
            'shell_variant' => $template->shell_variant,

            // Placeholder values also exposed to the shell so the outer chrome
            // (header, footer, contact ribbon, seal) can reflect the current
            // document's identity.
            'certificate_number' => $context['certificate_number'] ?? '',
            'letter_reference' => $context['letter_reference'] ?? '',
            'verification_url' => $context['verification_url'] ?? '',
            'organization' => $context['organization'] ?? config('app.name'),
            'org_full_name' => $context['org_full_name'] ?? 'Centre for Humanitarian Research and Social Development Foundation (CHRSD)',
            'org_short' => $context['org_short'] ?? 'CHRSD',
            'contact_phone' => $context['contact_phone'] ?? '+880-2-47122566',
            'contact_email' => $context['contact_email'] ?? 'info@chrsd.org',
            'contact_web' => $context['contact_web'] ?? 'www.chrsd.org',
            'footer_address' => $context['footer_address'] ?? '29 Toyenbee Circular Road (5th Floor), Motijheel C/A, Dhaka-1000, Bangladesh.',
            'reg_no' => $context['reg_no'] ?? 'S-14480/2026',
            'photo_url' => $context['photo_url'] ?? null,
            'qr_raw' => (string) ($context['qr_code'] ?? ''),
            'course_name' => $context['course_name'] ?? '',
            'verify_code' => $context['verify_code'] ?? '',
            'signatory_name' => $context['signatory_name'] ?? '',
            'signatory_title' => $context['signatory_title'] ?? '',
            // Two-signatory data — surfaced so the certificate shell can
            // render a fixed-position signature strip that never overlaps
            // the QR badge (was the sig-tbl-inside-body-flow bug).
            'signatory_1_name' => $context['signatory_1_name'] ?? '',
            'signatory_1_title' => $context['signatory_1_title'] ?? '',
            'signatory_1_sig' => $context['signatory_1_sig'] ?? '',
            'signatory_2_name' => $context['signatory_2_name'] ?? '',
            'signatory_2_title' => $context['signatory_2_title'] ?? '',
            'signatory_2_sig' => $context['signatory_2_sig'] ?? '',
            'issue_date' => $context['issue_date'] ?? ($context['date'] ?? ''),
            'issuer_name' => $context['issuer_name'] ?? 'CHRSD LEARNING',
            'issuer_tagline' => $context['issuer_tagline'] ?? 'Centre for Humanitarian Research',
        ])->render();
    }

    /**
     * Public helper: available placeholder keys, for the UI legend.
     *
     * @return array<string, string>
     */
    public static function knownPlaceholders(): array
    {
        return [
            '{{name}}' => 'Recipient full name',
            '{{designation}}' => 'Recipient designation / role',
            '{{organization}}' => 'Issuing organization name',
            '{{certificate_number}}' => 'Serial (for certificates)',
            '{{letter_reference}}' => 'Serial (for letters)',
            '{{date}}' => 'Issue date',
            '{{event_name}}' => 'Event or course name',
            '{{course_name}}' => 'Course name (course-completion certificates)',
            '{{position}}' => 'Position held',
            '{{department}}' => 'Department name',
            '{{duration}}' => 'Duration (e.g. "6 weeks")',
            '{{employee_id}}' => 'Employee serial (EMP-YYYY-######)',
            '{{program_name}}' => 'Programme name (for volunteer ID cards)',
            '{{blood_group}}' => 'Blood group (ID cards)',
            '{{nationality}}' => 'Nationality (ID cards)',
            '{{valid_from}}' => 'Valid-from date (formatted)',
            '{{valid_until}}' => 'Valid-until date (formatted)',
            '{{subject}}' => 'Letter subject line',
            '{{body}}' => 'Letter free-form body',
            '{{recipient_name}}' => 'Letter recipient name',
            '{{recipient_title}}' => 'Letter recipient title',
            '{{recipient_address}}' => 'Letter recipient address (multiline OK)',
            '{{signatory_name}}' => 'Signatory name',
            '{{signatory_title}}' => 'Signatory title',
            '{{signatory_left}}' => 'Left signatory name (for two-signature certificates)',
            '{{signatory_right}}' => 'Right signatory name (for two-signature certificates)',
            '{{verification_url}}' => 'Full verify URL',
            '{{{qr_code}}}' => 'Inline SVG/PNG QR code (triple-brace, raw)',
            '{{issue_date}}' => 'Formatted issue date',
        ];
    }
}
