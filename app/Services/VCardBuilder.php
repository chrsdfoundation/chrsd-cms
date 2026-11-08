<?php

namespace App\Services;

use App\Models\BusinessCard;

class VCardBuilder
{
    public function build(BusinessCard|array $data): string
    {
        $fields = $data instanceof BusinessCard ? $data->toArray() : $data;

        $lines = [];
        $lines[] = 'BEGIN:VCARD';
        $lines[] = 'VERSION:3.0';

        $fn = trim($fields['full_name'] ?? '');
        if ($fn !== '') {
            $lines[] = 'FN:' . $this->escape($fn);
            $lines[] = 'N:' . $this->escapeComponent($this->guessLastName($fn))
                . ';' . $this->escapeComponent($this->guessFirstName($fn)) . ';;;';
        }

        $org = trim($fields['company_name'] ?? '');
        if ($org !== '') {
            $lines[] = 'ORG:' . $this->escape($org);
        }

        $title = trim($fields['job_title'] ?? '');
        if ($title !== '') {
            $lines[] = 'TITLE:' . $this->escape($title);
        }

        $phone = trim($fields['phone'] ?? '');
        if ($phone !== '') {
            $lines[] = 'TEL;TYPE=WORK,VOICE:' . $this->escape($phone);
        }

        $mobile = trim($fields['mobile'] ?? '');
        if ($mobile !== '') {
            $lines[] = 'TEL;TYPE=CELL:' . $this->escape($mobile);
        }

        $whatsapp = trim($fields['whatsapp'] ?? '');
        if ($whatsapp !== '') {
            $cleaned = preg_replace('/[^0-9+]/', '', $whatsapp);
            $lines[] = 'URL;TYPE=WhatsApp:https://wa.me/' . ltrim($cleaned, '+');
        }

        $workEmail = trim($fields['work_email'] ?? '');
        if ($workEmail !== '') {
            $lines[] = 'EMAIL;TYPE=WORK:' . $this->escape($workEmail);
        }

        $personalEmail = trim($fields['personal_email'] ?? '');
        if ($personalEmail !== '') {
            $lines[] = 'EMAIL;TYPE=HOME:' . $this->escape($personalEmail);
        }

        $website = trim($fields['website'] ?? '');
        if ($website !== '') {
            $lines[] = 'URL:' . $this->escape($website);
        }

        $linkedin = trim($fields['linkedin'] ?? '');
        if ($linkedin !== '') {
            $url = $this->normalizeLinkedIn($linkedin);
            $lines[] = 'URL;TYPE=LinkedIn:' . $this->escape($url);
        }

        $address = trim($fields['address'] ?? '');
        if ($address !== '') {
            $lines[] = 'ADR;TYPE=WORK:;;' . $this->escapeComponent($address) . ';;;;';
        }

        $lines[] = 'END:VCARD';

        return implode("\r\n", $lines) . "\r\n";
    }

    protected function escape(string $value): string
    {
        $value = str_replace('\\', '\\\\', $value);
        $value = str_replace(',', '\\,', $value);
        $value = str_replace(';', '\\;', $value);
        $value = str_replace("\n", '\\n', $value);
        $value = str_replace("\r", '', $value);

        return $value;
    }

    protected function escapeComponent(string $value): string
    {
        return $this->escape($value);
    }

    protected function guessFirstName(string $fullName): string
    {
        $parts = preg_split('/\s+/', trim($fullName));

        return $parts[0] ?? '';
    }

    protected function guessLastName(string $fullName): string
    {
        $parts = preg_split('/\s+/', trim($fullName));

        return count($parts) > 1 ? end($parts) : '';
    }

    protected function normalizeLinkedIn(string $input): string
    {
        $input = trim($input);

        if (preg_match('#^https?://#i', $input)) {
            return $input;
        }

        // Bare host, e.g. "www.linkedin.com/in/maramim" or "linkedin.com/company/x":
        // keep the path and add the scheme, so the profile prefix is not doubled.
        if (preg_match('#^(?:www\.)?linkedin\.com/(.*)$#i', $input, $matches)) {
            return 'https://www.linkedin.com/' . ltrim($matches[1], '/');
        }

        // Plain handle, "@maramim", "/maramim" or "in/maramim".
        $handle = preg_replace('#^in/#i', '', ltrim($input, '@/'));

        return 'https://www.linkedin.com/in/' . $handle;
    }
}
