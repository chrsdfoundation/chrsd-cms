<?php

namespace App\Services\BusinessCard;

/**
 * The laid-out card: primitives for both sides, the validation issues, and the QR
 * facts. Built by CardLayoutEngine, consumed by the renderers and the preview.
 */
final readonly class CardLayout
{
    /**
     * @param  list<array<string, mixed>>  $front
     * @param  list<array<string, mixed>>  $back
     * @param  list<array<string, mixed>>  $issues
     * @param  array{modules: int, module_mm: float, quiet_zone_modules: int, profile: string}  $qr
     */
    public function __construct(
        public array $front,
        public array $back,
        public array $issues,
        public array $qr,
        public string $specVersion,
        public string $profile,
    ) {}

    /** @return list<array<string, mixed>> */
    public function errors(): array
    {
        return array_values(array_filter($this->issues, fn (array $i) => $i['severity'] === 'error'));
    }

    /** @return list<array<string, mixed>> */
    public function warnings(): array
    {
        return array_values(array_filter($this->issues, fn (array $i) => $i['severity'] === 'warning'));
    }

    public function requiresAcknowledgement(): bool
    {
        foreach ($this->issues as $issue) {
            if ($issue['requires_acknowledgement'] ?? false) {
                return true;
            }
        }

        return false;
    }

    public function isBlocked(): bool
    {
        return $this->errors() !== [];
    }
}
