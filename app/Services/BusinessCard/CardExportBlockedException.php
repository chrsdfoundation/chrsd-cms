<?php

namespace App\Services\BusinessCard;

use RuntimeException;

/**
 * An export was refused. The issues say why, so the user can fix the card rather than
 * guess. acknowledgementRequired means the card is printable only after the user
 * accepts that the QR modules are below the minimum size.
 */
final class CardExportBlockedException extends RuntimeException
{
    /** @param  list<array<string, mixed>>  $issues */
    public function __construct(
        public readonly array $issues,
        public readonly bool $acknowledgementRequired = false,
    ) {
        parent::__construct(implode(' ', array_map(fn (array $i) => $i['message'], $issues)) ?: 'The card cannot be exported.');
    }
}
