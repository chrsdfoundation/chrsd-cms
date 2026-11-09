<?php

namespace App\Services\BusinessCard;

use InvalidArgumentException;
use RuntimeException;

/**
 * The versioned design spec (docs/business-card/card-spec.json).
 *
 * The version is a short hash of the file, so every export records exactly which
 * geometry produced it. Values are read through dotted keys, e.g. "front.org_name.size_px".
 */
final class CardSpec
{
    private function __construct(
        private readonly array $data,
        public readonly string $version,
    ) {}

    public static function fromFile(string $path): self
    {
        $json = file_get_contents($path);
        if ($json === false) {
            throw new RuntimeException("Cannot read the card spec at [{$path}].");
        }

        $data = json_decode($json, true, 32, JSON_THROW_ON_ERROR);

        return new self($data, 'card-spec-' . substr(hash('sha256', $json), 0, 12));
    }

    public static function fromConfig(): self
    {
        return self::fromFile((string) config('business_card.spec_path'));
    }

    /** Value at a dotted path. Throws when the spec has no such key. */
    public function get(string $path): mixed
    {
        $node = $this->data;
        foreach (explode('.', $path) as $key) {
            if (! is_array($node) || ! array_key_exists($key, $node)) {
                throw new InvalidArgumentException("Card spec has no key [{$path}].");
            }
            $node = $node[$key];
        }

        return $node;
    }

    /** Design pixels to points. The spec defines 1 px = 0.24 pt (300 dpi). */
    public function pt(float $px): float
    {
        return $px * 0.24;
    }

    public function trimPt(): array
    {
        return $this->get('page.trim_pt');
    }

    /**
     * CMYK for a named colour, as fractions 0-1. The spec stores percentages.
     *
     * @return array{0: float, 1: float, 2: float, 3: float}
     */
    public function colour(string $name): array
    {
        return $this->percentages($this->get("colours.{$name}.cmyk"));
    }

    /** CMYK estimate for a logo gradient stop, from the spec's hex table (fractions 0-1). */
    public function gradientStopCmyk(string $hex): array
    {
        return $this->percentages($this->get('logo_gradient_stops_cmyk_estimates.' . strtolower($hex)));
    }

    /** @return array{0: float, 1: float, 2: float, 3: float} */
    private function percentages(array $values): array
    {
        return array_map(fn ($v) => $v / 100, $values);
    }
}
