<?php
// Generate QR code SVG for the certificate verification URL
use SimpleSoftwareIO\QrCode\Facades\QrCode;

echo QrCode::format('svg')
    ->size(200)
    ->margin(0)
    ->errorCorrection('M')
    ->generate($url);
?>
