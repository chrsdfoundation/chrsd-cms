<?php

return [

    /*
    |--------------------------------------------------------------------------
    | HR inbox
    |--------------------------------------------------------------------------
    |
    | Where to route "new certificate request" notifications when an employee
    | submits one via the portal. Options (in priority order):
    |
    |   1. CMS_HR_INBOX env var — a single email address (simplest)
    |   2. All users with the 'hr_manager' or 'super_admin' role — fallback
    |
    */
    'hr_inbox' => env('CMS_HR_INBOX'),

    'hr_fallback_roles' => ['hr_manager', 'super_admin'],

];
