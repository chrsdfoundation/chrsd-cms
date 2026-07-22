<?php

namespace App\Observers;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentEventType;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmploymentEvent;
use App\Models\Position;
use Illuminate\Support\Facades\Auth;

class EmployeeStateObserver
{
    /**
     * Terminal statuses that trigger an "Ended" event rather than a plain
     * "StatusChange" — kept in one place so it's easy to add more later.
     */
    protected const TERMINAL_STATUSES = [
        EmployeeStatus::Terminated,
        EmployeeStatus::Resigned,
        EmployeeStatus::Retired,
    ];

    public function created(Employee $employee): void
    {
        $this->log(
            $employee,
            EmploymentEventType::Hire,
            $employee->hired_at?->toDateString() ?? now()->toDateString(),
            null,
            $this->snapshot($employee),
        );
    }

    public function updated(Employee $employee): void
    {
        $previous = $this->snapshotFromOriginals($employee);
        $current = $this->snapshot($employee);

        // Position change → promotion (semantic: any position transition is
        // "promotion" from a records point of view — the direction is legible
        // from the previous_state/new_state snapshots).
        if ($employee->wasChanged('position_id')) {
            $this->log($employee, EmploymentEventType::Promotion, now()->toDateString(), $previous, $current);
        }

        if ($employee->wasChanged('department_id')) {
            $this->log($employee, EmploymentEventType::Transfer, now()->toDateString(), $previous, $current);
        }

        if ($employee->wasChanged('employment_type')) {
            $this->log($employee, EmploymentEventType::ContractChange, now()->toDateString(), $previous, $current);
        }

        if ($employee->wasChanged('employee_status')) {
            $type = in_array($employee->employee_status, self::TERMINAL_STATUSES, true)
                ? EmploymentEventType::Ended
                : EmploymentEventType::StatusChange;

            $when = $employee->ended_at?->toDateString() ?? now()->toDateString();

            $this->log($employee, $type, $when, $previous, $current);
        }
    }

    protected function log(
        Employee $employee,
        EmploymentEventType $type,
        string $on,
        ?array $previous,
        ?array $new,
    ): void {
        EmploymentEvent::create([
            'employee_id' => $employee->id,
            'event_type' => $type,
            'occurred_on' => $on,
            'previous_state' => $previous,
            'new_state' => $new,
            'recorded_by_user_id' => Auth::id(),
        ]);
    }

    /**
     * Fields captured per snapshot. Names are looked up fresh by ID so a
     * later rename never blurs the timeline AND so we're not tripped up
     * by Eloquent's cached relations after an ->update().
     */
    protected function snapshot(Employee $employee): array
    {
        return $this->buildSnapshot(
            departmentId: $employee->department_id,
            positionId: $employee->position_id,
            employmentType: $employee->employment_type?->value ?? $employee->getAttributes()['employment_type'] ?? null,
            employeeStatus: $employee->employee_status?->value ?? $employee->getAttributes()['employee_status'] ?? null,
        );
    }

    /** Snapshot of the record's ORIGINAL (pre-update) values. */
    protected function snapshotFromOriginals(Employee $employee): array
    {
        return $this->buildSnapshot(
            departmentId: $employee->getOriginal('department_id'),
            positionId: $employee->getOriginal('position_id'),
            employmentType: $employee->getOriginal('employment_type'),
            employeeStatus: $employee->getOriginal('employee_status'),
        );
    }

    protected function buildSnapshot(
        ?int $departmentId,
        ?int $positionId,
        mixed $employmentType,
        mixed $employeeStatus,
    ): array {
        return [
            'department_id' => $departmentId,
            'department_name' => $departmentId ? optional(Department::find($departmentId))->name : null,
            'position_id' => $positionId,
            'position_title' => $positionId ? optional(Position::find($positionId))->title : null,
            'employment_type' => $this->stringify($employmentType),
            'employee_status' => $this->stringify($employeeStatus),
        ];
    }

    protected function stringify(mixed $v): ?string
    {
        if ($v === null) {
            return null;
        }
        if ($v instanceof \BackedEnum) {
            return (string) $v->value;
        }

        return (string) $v;
    }
}
