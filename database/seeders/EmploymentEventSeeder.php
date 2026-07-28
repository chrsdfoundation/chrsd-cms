<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmploymentEvent;
use Illuminate\Database\Seeder;

class EmploymentEventSeeder extends Seeder
{
    /**
     * Seed the employment-event history for the real employees. Employee is
     * resolved by email; must run after EmployeeSeeder. Keyed on
     * employee + event_type + occurred_on for idempotency. recorded_by_user_id
     * is left null (users are not migrated via git).
     */
    public function run(): void
    {
        foreach (require __DIR__ . '/data/sync_employment_events.php' as $r) {
            // Hire events are created automatically by EmployeeStateObserver
            // when EmployeeSeeder inserts the employee — skip them here to
            // avoid duplicates; seed only the transfer/promotion history.
            if ($r['event_type'] === 'hire') {
                continue;
            }

            $employeeId = Employee::where('email', $r['employee_email'])->value('id');

            if ($employeeId === null) {
                continue;
            }

            EmploymentEvent::updateOrCreate(
                [
                    'employee_id' => $employeeId,
                    'event_type' => $r['event_type'],
                    'occurred_on' => $r['occurred_on'],
                ],
                [
                    'previous_state' => $r['previous_state'],
                    'new_state' => $r['new_state'],
                    'notes' => $r['notes'],
                    'organization_id' => $r['organization_id'],
                ],
            );
        }
    }
}
