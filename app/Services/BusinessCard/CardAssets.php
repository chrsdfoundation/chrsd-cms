<?php

namespace App\Services\BusinessCard;

use App\Services\BusinessCard\Pdf\TrueTypeFont;
use InvalidArgumentException;
use RuntimeException;

/**
 * Loaded once per process: the converted logo and icon geometry, the static fonts,
 * and the QR matrix encoder. Nothing here touches the network or runs a tool.
 */
final class CardAssets
{
    /** @var array<string, TrueTypeFont> */
    private static array $fonts = [];

    /** @var array<string, mixed>|null */
    private static ?array $geometry = null;

    public static function geometry(): array
    {
        if (self::$geometry === null) {
            $path = (string) config('business_card.geometry_path');
            $json = file_get_contents($path);
            if ($json === false) {
                throw new RuntimeException("Card geometry is missing at [{$path}]. Run docs/business-card/tools/export_card_geometry.py.");
            }
            self::$geometry = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        }

        return self::$geometry;
    }

    /** @return array{viewBox: list<float>, paths: list<array{d: list<list<float|string>>, gradient: ?string}>, gradients: array} */
    public static function logo(): array
    {
        return self::geometry()['logo'];
    }

    /** @return list<list<list<float|string>>> one path (command list) per icon subpath */
    public static function icon(string $name): array
    {
        $icons = self::geometry()['icons'];
        if (! isset($icons[$name])) {
            throw new InvalidArgumentException("Unknown card icon [{$name}].");
        }

        return $icons[$name];
    }

    public static function font(string $key): TrueTypeFont
    {
        if (! isset(self::$fonts[$key])) {
            $file = config("business_card.fonts.{$key}");
            if (! is_string($file)) {
                throw new InvalidArgumentException("No font is configured for [{$key}].");
            }
            self::$fonts[$key] = TrueTypeFont::fromFile(rtrim((string) config('business_card.font_dir'), '/') . '/' . $file);
        }

        return self::$fonts[$key];
    }

    /** Forget cached assets. For tests that change config. */
    public static function flush(): void
    {
        self::$fonts = [];
        self::$geometry = null;
    }
}
