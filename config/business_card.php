<?php

/*
 * Executive business card: print settings.
 *
 * Values marked UNVERIFIED are assumptions from the design brief. They need the
 * printer's confirmation (see docs/business-card/DEPLOY.md) and are set here, not
 * hard-coded in the layout.
 */

return [

    // Design geometry comes from the spec file, versioned with the export.
    'spec_path' => base_path('docs/business-card/card-spec.json'),
    'geometry_path' => resource_path('business-card/geometry.json'),
    'font_dir' => resource_path('fonts/business-card'),

    'fonts' => [
        // Keys match card-spec.json "fonts". Files are static TrueType (OFL).
        'sans_regular' => 'Montserrat-Regular.ttf',
        'sans_semibold' => 'Montserrat-SemiBold.ttf',
        'serif_org' => 'CormorantGaramond-SemiBold.ttf',
    ],

    // Minimum font size on the card, in points (card-spec print_constants).
    'min_font_pt' => 6.0,

    // UNVERIFIED: the printer's real foil minimum line weight is unknown.
    'foil_min_line_pt' => 0.75,

    // UNVERIFIED: a QR module below this size is hard to scan on a card printer.
    // Below it, export needs an acknowledgement, stored in the audit row.
    'qr_min_module_mm' => 0.33,

    // QR quiet zone, in modules (ISO/IEC 18004 asks for four).
    'qr_quiet_zone_modules' => 4,

    'qr_default_profile' => 'compact',

    // UNVERIFIED: typical convention; confirm with the printer.
    'max_total_ink_percent' => 300,

    // Bleed and trim come from the spec. These are the values the brief assumed.
    'bleed_in' => 0.125,

    'engine' => [
        'name' => 'chrsd-pure-php-pdf',
        'version' => '1.0.0',
    ],

    // Exported PDFs and their audit files contain personal contact data. They go on
    // the private disk, never on the public one.
    'export_disk' => 'local',
    'export_directory' => 'business-cards',
];
