<?php

namespace App\Services\Documents;

use App\Enums\VerificationStatus;
use App\Models\Certificate;
use App\Models\IdCard;
use App\Models\User;
use App\Notifications\DocumentExpired;
use App\Notifications\DocumentExpiring;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

/**
 * Nightly scan for verifiable documents approaching or past their valid_until.
 *
 * Contract per document:
 *   1. First run once the doc enters the 30-day window → SOON notice
 *   2. About 21 days later (as it enters the 7-day window) → URGENT notice
 *   3. Day after valid_until → EXPIRED notice + status flip Valid → Expired
 *
 * Anti-spam guard: a doc that received any notice in the last 21 days is
 * skipped for the "expiring" bucket. The expired bucket ignores the guard
 * because it only fires once (once status=Expired, the query excludes it).
 */
class ExpiryScannerService
{
    /** Days-out window for early warning. */
    public const SOON_WINDOW_DAYS = 30;

    /** Rate limit between renewal reminders on the same doc. */
    public const RENOTIFY_AFTER_DAYS = 21;

    /**
     * @return array{expiring: int, expired: int, notified_hr: int, ran_at: string}
     */
    public function run(?Carbon $today = null): array
    {
        $today ??= Carbon::today();

        $stats = ['expiring' => 0, 'expired' => 0, 'notified_hr' => 0];

        foreach ([Certificate::class, IdCard::class] as $modelClass) {
            $stats['expired'] += $this->handleExpired($modelClass, $today, $stats);
            $stats['expiring'] += $this->handleExpiring($modelClass, $today);
        }

        return $stats + ['ran_at' => $today->toDateString()];
    }

    protected function handleExpired(string $modelClass, Carbon $today, array &$stats): int
    {
        $count = 0;

        $query = $modelClass::query()
            ->acrossOrganizations()
            ->where('status', VerificationStatus::Valid->value)
            ->whereNotNull('valid_until')
            ->whereDate('valid_until', '<', $today);

        foreach ($query->cursor() as $doc) {
            $doc->forceFill([
                'status' => VerificationStatus::Expired,
                'expiry_notified_at' => now(),
            ])->save();

            $this->notifySubject($doc, new DocumentExpired($doc));
            $stats['notified_hr'] += $this->notifyHr(new DocumentExpired($doc));

            $count++;
        }

        return $count;
    }

    protected function handleExpiring(string $modelClass, Carbon $today): int
    {
        $count = 0;
        $windowEnd = $today->copy()->addDays(self::SOON_WINDOW_DAYS);
        $renotifyCutoff = $today->copy()->subDays(self::RENOTIFY_AFTER_DAYS);

        $query = $modelClass::query()
            ->acrossOrganizations()
            ->where('status', VerificationStatus::Valid->value)
            ->whereNotNull('valid_until')
            ->whereDate('valid_until', '>=', $today)
            ->whereDate('valid_until', '<=', $windowEnd)
            ->where(function ($q) use ($renotifyCutoff) {
                $q->whereNull('expiry_notified_at')
                    ->orWhere('expiry_notified_at', '<', $renotifyCutoff);
            });

        foreach ($query->cursor() as $doc) {
            $days = (int) $today->diffInDays($doc->valid_until, false);

            $doc->forceFill(['expiry_notified_at' => now()])->save();
            $this->notifySubject($doc, new DocumentExpiring($doc, $days));
            $count++;
        }

        return $count;
    }

    protected function notifySubject(Model $doc, $notification): void
    {
        $employee = $doc->employee ?? null;
        if ($employee && ! empty($employee->email)) {
            $employee->notify($notification);
        }
    }

    /**
     * Route to HR inbox / fallback roles. Mirrors CertificateRequestService::notifyHr().
     * Returns 1 if any HR recipient was notified, 0 otherwise.
     */
    protected function notifyHr($notification): int
    {
        $inbox = config('cms.hr_inbox');
        if (! empty($inbox)) {
            Notification::route('mail', $inbox)->notify($notification);

            return 1;
        }

        $wanted = config('cms.hr_fallback_roles', ['hr_manager', 'super_admin']);
        $existing = Role::query()
            ->whereIn('name', $wanted)
            ->pluck('name')
            ->all();

        if (empty($existing)) {
            return 0;
        }

        $recipients = User::query()
            ->role($existing)
            ->whereNotNull('email')
            ->get();

        if ($recipients->isEmpty()) {
            return 0;
        }

        Notification::send($recipients, $notification);

        return 1;
    }
}
