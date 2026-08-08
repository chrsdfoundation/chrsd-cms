<?php

namespace App\Console\Commands;

use App\Models\Certificate;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CertificateIssueCommand extends Command
{
    protected $signature = 'certificate:issue {--name= : Recipient name} {--program= : Program name} {--org= : Organization ID (default: 1)} {--issued-on= : Issue date (Y-m-d format, default: today)}';

    protected $description = 'Issue a new certificate with auto-allocated number and verification hash';

    public function handle()
    {
        $name = $this->option('name');
        $program = $this->option('program');
        $orgId = $this->option('org') ?? 1;
        $issuedOn = $this->option('issued-on') ? Carbon::parse($this->option('issued-on')) : now();

        if (! $name || ! $program) {
            $this->error('--name and --program are required');

            return self::FAILURE;
        }

        try {
            $cert = Certificate::issue([
                'organization_id' => $orgId,
                'recipient_name' => $name,
                'program_name' => $program,
                'issued_on' => $issuedOn,
                'certificate_title' => 'Certificate of Achievement',
                'award_lead_in' => 'has successfully completed',
                'signatory_1_name' => 'Razib Mustafiz',
                'signatory_1_title' => 'Project Coordinator',
                'signatory_2_name' => 'M.A. Ramim',
                'signatory_2_title' => 'Executive Director',
            ]);

            $this->info('Certificate issued successfully');
            $this->info("Certificate No: {$cert->certificate_no}");
            $this->info("Verify URL: {$cert->verify_url}");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to issue certificate: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
