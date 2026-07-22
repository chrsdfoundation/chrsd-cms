<?php

return [

    /*
     * The single organization this CMS instance belongs to.
     * These values seed the global scope middleware and are used anywhere
     * "current organization" context is needed without a session lookup.
     */
    'org_name' => env('CHRSD_ORG_NAME', 'CHRSD Foundation'),
    'org_code' => env('CHRSD_ORG_CODE', 'CHRSD'),

    /*
     * The primary key of the Organization row for CHRSD Foundation.
     * Set CHRSD_ORG_ID in .env after running db:seed if the ID differs from 1.
     */
    'org_id' => (int) env('CHRSD_ORG_ID', 1),

    /*
     * Support / HR inbox used as the fallback when CMS_HR_INBOX is not set.
     */
    'support_email' => env('CMS_HR_INBOX', 'ed@chrsd.org'),

    /*
     * Public verification base URL. Defaults to APP_URL/verify.
     * Override with CHRSD_VERIFY_URL if the verify subdomain is different.
     */
    'verify_url' => env('CHRSD_VERIFY_URL', null),

    /*
     * Origin of the external Website that renders verify pages (no trailing
     * slash, no /verify suffix — the QR code service appends the /verify/{hash}
     * path). Falls back to APP_URL when unset, so the CMS keeps serving its
     * own /verify routes if nothing is configured.
     */
    'verify_base_url' => env('VERIFY_BASE_URL'),

    /*
     * Puppeteer / Browsershot binary paths. These MUST live in config (not
     * env() at runtime) because production runs with `php artisan config:cache`
     * — env() then returns null and Chromium fails to launch.
     */
    'puppeteer' => [
        'chromium' => env('CHROMIUM_PATH'),
        'node' => env('NODE_PATH', 'node'),
    ],

];
