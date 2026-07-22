<?php

namespace Tests\Feature;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentEventType;
use App\Enums\EmploymentType;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmploymentHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected Department $hr;
    protected Department $fin;
    protected Position $staff;
    protected Position $lead;
    protected Position $acct;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hr  = Department::create(['code' => 'HR',  'name' => 'Human Resources']);
        $this->fin = Department::create(['code' => 'FIN', 'name' => 'Finance']);
        $this->staff = Position::create(['department_id' => $this->hr->id,  'code' => 'HR-STAFF', 'title' => 'HR Staff']);
        $this->lead  = Position::create(['department_id' => $this->hr->id,  'code' => 'HR-LEAD',  'title' => 'HR Lead']);
        $this->acct  = Position::create(['department_id' => $this->fin->id, 'code' => 'FIN-ACCT', 'title' => 'Accountant']);
    }

    protected function makeEmp(): Employee
    {
        return Employee::create([
            'first_name'    => 'Jane', 'last_name' => 'Doe',
            'email'         => 'jane.doe@example.com',
            'department_id' => $this->hr->id,
            'position_id'   => $this->staff->id,
            'hired_at'      => now()->subYear(),
        ]);
    }

    public function test_hire_event_is_logged_on_employee_create(): void
    {
        $e = $this->makeEmp();

        $this->assertCount(1, $e->history);
        $hire = $e->history->first();

        $this->assertSame(EmploymentEventType::Hire, $hire->event_type);
        $this->assertSame($this->staff->title, $hire->new_state['position_title']);
        $this->assertSame($this->hr->name, $hire->new_state['department_name']);
    }

    public function test_position_change_creates_promotion_event(): void
    {
        $e = $this->makeEmp();
        $e->update(['position_id' => $this->lead->id]);

        $promotion = $e->history()->where('event_type', EmploymentEventType::Promotion)->latest('id')->first();

        $this->assertNotNull($promotion);
        $this->assertSame('HR Staff', $promotion->previous_state['position_title']);
        $this->assertSame('HR Lead',  $promotion->new_state['position_title']);
    }

    public function test_department_change_creates_transfer_event(): void
    {
        $e = $this->makeEmp();
        $e->update(['department_id' => $this->fin->id, 'position_id' => $this->acct->id]);

        $transfer = $e->history()->where('event_type', EmploymentEventType::Transfer)->first();

        $this->assertNotNull($transfer);
        $this->assertSame('Human Resources', $transfer->previous_state['department_name']);
        $this->assertSame('Finance',         $transfer->new_state['department_name']);
    }

    public function test_contract_change_creates_contract_change_event(): void
    {
        // Explicit initial employment_type so previous_state has a value to diff against.
        $e = tap($this->makeEmp())->update(['employment_type' => EmploymentType::Regular->value]);
        $e->refresh();

        $e->update(['employment_type' => EmploymentType::Contractual->value]);

        $ev = $e->history()->where('event_type', EmploymentEventType::ContractChange)->first();

        $this->assertNotNull($ev);
        $this->assertSame(EmploymentType::Regular->value, $ev->previous_state['employment_type']);
        $this->assertSame(EmploymentType::Contractual->value, $ev->new_state['employment_type']);
    }

    public function test_terminal_status_creates_ended_event_not_status_change(): void
    {
        $e = $this->makeEmp();
        $e->update(['employee_status' => EmployeeStatus::Retired->value, 'ended_at' => now()]);

        $this->assertTrue(
            $e->history()->where('event_type', EmploymentEventType::Ended)->exists(),
            'Retiring should produce an Ended event',
        );
        $this->assertFalse(
            $e->history()->where('event_type', EmploymentEventType::StatusChange)->exists(),
            'Retiring must NOT produce a StatusChange event',
        );
    }

    public function test_non_terminal_status_creates_status_change_event(): void
    {
        $e = $this->makeEmp();
        $e->update(['employee_status' => EmployeeStatus::OnLeave->value]);

        $this->assertTrue(
            $e->history()->where('event_type', EmploymentEventType::StatusChange)->exists(),
        );
    }

    public function test_snapshots_are_rename_safe(): void
    {
        $e = $this->makeEmp();
        $e->update(['position_id' => $this->lead->id]);
        $promotion = $e->history()->where('event_type', EmploymentEventType::Promotion)->first();

        // Rename the position AFTER the event was recorded
        $this->lead->update(['title' => 'Head of HR']);

        // The event snapshot must retain the original title, not follow the rename.
        $this->assertSame('HR Lead', $promotion->new_state['position_title']);
    }
}
