<?php

namespace App\Console\Commands;

use App\Services\Documents\ExpiryScannerService;
use Illuminate\Console\Command;

class ScanDocumentExpiryCommand extends Command
{
    protected $signature = 'documents:expiry-scan {--dry-run : Only print counts, do not modify or notify}';

    protected $description = 'Scan Certificates and ID Cards for approaching / past expiry; notify subjects + HR.';

    public function handle(ExpiryScannerService $scanner): int
    {
        if ($this->option('dry-run')) {
            $this->warn('Dry-run: no notifications will be sent and no rows will be modified.');
            $this->line('(Preview implementation intentionally minimal — wire real preview logic once needed.)');
            return self::SUCCESS;
        }

        $stats = $scanner->run();

        $this->info(sprintf(
            '[%s] expiring: %d, expired: %d, HR broadcasts: %d',
            $stats['ran_at'],
            $stats['expiring'],
            $stats['expired'],
            $stats['notified_hr'],
        ));

        return self::SUCCESS;
    }
}
