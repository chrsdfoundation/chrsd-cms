<?php

namespace App\Services\BusinessCard;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;

/**
 * QR module matrix for the card, from BaconQrCode (already installed through
 * simplesoftwareio/simple-qrcode). ECC level M, as the spec recommends.
 *
 * Only the matrix is produced here. The renderer draws it as vector rectangles,
 * so the QR is never a raster image.
 */
final class CardQrCode
{
    /**
     * @return array{modules: int, rows: list<list<bool>>} rows[y][x] is true for a dark module
     */
    public function matrix(string $payload): array
    {
        $code = Encoder::encode($payload, ErrorCorrectionLevel::M());
        $matrix = $code->getMatrix();

        $size = $matrix->getWidth();
        $rows = [];
        for ($y = 0; $y < $size; $y++) {
            $row = [];
            for ($x = 0; $x < $size; $x++) {
                $row[] = $matrix->get($x, $y) === 1;
            }
            $rows[] = $row;
        }

        return ['modules' => $size, 'rows' => $rows];
    }
}
