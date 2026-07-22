<?php

namespace App\Services\Documents;

use App\Enums\CertificateIssuance;
use App\Models\Certificate;
use App\Models\CertificateType;
use App\Models\Employee;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class BulkCertificateIssuanceService
{
    public function __construct(protected CertificateGeneratorService $generator) {}

    /**
     * Issue a certificate to every employee in $employees. Each row is its own
     * transaction — a PDF-render failure on one employee does not roll back
     * the certificates already generated for prior employees.
     *
     * Returns a result envelope with counts + the list of failure reasons.
     *
     * @param  Collection<Employee>  $employees
     */
    public function run(
        Collection $employees,
        CertificateType $type,
        ?Employee $signatory = null,
        ?string $purpose = null,
        bool $autoGenerate = true,
    ): array {
        $succeeded = [];
        $failed    = [];

        foreach ($employees as $employee) {
            try {
                DB::transaction(function () use ($employee, $type, $signatory, $purpose, $autoGenerate, &$succeeded) {
                    $cert = Certificate::create([
                        'employee_id'         => $employee->id,
                        'certificate_type_id' => $type->id,
                        'signed_by_id'        => $signatory?->id,
                        'purpose'             => $purpose,
                        'issuance_status'     => CertificateIssuance::Draft,
                        'issued_on'           => now()->toDateString(),
                        'valid_until'         => $type->validity_days
                            ? now()->addDays($type->validity_days)->toDateString()
                            : null,
                    ]);

                    if ($autoGenerate) {
                        $this->generator->generate($cert);
                    }

                    $succeeded[] = $cert->serial_number;
                });
            } catch (Throwable $e) {
                $failed[] = [
                    'employee' => $employee->full_name,
                    'reason'   => $e->getMessage(),
                ];
                Log::warning('Bulk certificate issuance failed for one employee', [
                    'employee_id' => $employee->id,
                    'exception'   => $e->getMessage(),
                ]);
            }
        }

        return [
            'total'      => $employees->count(),
            'succeeded'  => $succeeded,
            'failed'     => $failed,
        ];
    }
}
