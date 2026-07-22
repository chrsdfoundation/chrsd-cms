<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Nightly document expiry scan — notifies employees + HR about expiring/expired
// certificates and ID cards, and auto-flips status Valid → Expired.
Schedule::command('documents:expiry-scan')
    ->dailyAt('07:00')
    ->withoutOverlapping()
    ->onOneServer();

// Nightly pledge sweep — flips Open pledges past their due_date to Overdue so
// they surface in the Fundraising dashboard tile.
Schedule::command('pledges:sweep-overdue')
    ->dailyAt('07:05')
    ->withoutOverlapping()
    ->onOneServer();
