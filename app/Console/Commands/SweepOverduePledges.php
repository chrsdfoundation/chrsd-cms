<?php

namespace App\Console\Commands;

use App\Enums\PledgeStatus;
use App\Models\Pledge;
use Illuminate\Console\Command;

class SweepOverduePledges extends Command
{
    protected $signature = 'pledges:sweep-overdue';

    protected $description = 'Flip Open pledges whose due_date is in the past to Overdue.';

    public function handle(): int
    {
        $affected = Pledge::query()
            ->acrossOrganizations()
            ->where('status', PledgeStatus::Open->value)
            ->whereDate('due_date', '<', now()->toDateString())
            ->update(['status' => PledgeStatus::Overdue->value]);

        $this->info("Marked {$affected} pledge(s) overdue.");
        return self::SUCCESS;
    }
}
