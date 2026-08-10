<?php

/**
 * QR Code Configuration
 *
 * This configuration file centralizes all QR code generation settings
 * to ensure system-wide consistency across all documents:
 * - ID Cards (front & back)
 * - Certificates
 * - Official Letters
 * - Money Receipts
 *
 * Standardized on:
 * - Encoding format: SVG (preferred) or PNG data URI (fallback)
 * - Module size: 5 (DNS2D library units, ~150-200px at 96dpi)
 * - Verification URL: Points to https://chrsd.org/verify or http://127.0.0.1:8001/verify
 * - Identifier: Uses verification_hash (preferred) or serial_number fallback
 */

return [

    /*
     * Standard module size for all QR codes (DNS2D library units)
     * - Size 5 creates approximately 150-200px QR code at 96dpi
     * - Consistent across all printed and digital documents
     * - Suitable for scanning from 30cm+ distance
     */
    'default_module_size' => 5,

    /*
     * Verification base URL configuration
     *
     * In production: https://chrsd.org
     * In development (local): http://127.0.0.1:8001
     *
     * This is configured via VERIFY_BASE_URL environment variable
     * and handled by App\Services\QrCodeService::verificationUrl()
     */
    'verify_base_url' => env('VERIFY_BASE_URL'),

    /*
     * QR code format preferences by context
     *
     * SVG: Preferred for PDFs and HTML (scalable, self-contained)
     * PNG: Fallback for DomPDF and embedded image contexts
     */
    'formats' => [
        'default' => 'svg',    // Standard for all print operations
        'fallback' => 'png',   // For compatibility with older renderers
    ],

    /*
     * Document-specific settings
     * (Kept for reference; all now use default_module_size)
     */
    'documents' => [
        'id_card' => [
            'module_size' => 5,  // Default
            'locations' => ['front_bottom_right', 'back_bottom'],
        ],
        'certificate' => [
            'module_size' => 5,  // Default
            'locations' => ['top_left'],
        ],
        'official_letter' => [
            'module_size' => 5,  // Default
            'locations' => ['bottom_left'],
        ],
        'money_receipt' => [
            'module_size' => 5,  // Default
            'locations' => ['bottom'],
        ],
    ],

];
