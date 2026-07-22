<?php

namespace App\Models;

use App\Concerns\BelongsToOrganization;
use App\Enums\EmploymentEventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EmploymentEvent extends Model
{
    use BelongsToOrganization, LogsActivity;

    protected $fillable = [
        'organization_id',
        'employee_id',
        'event_type',
        'occurred_on',
        'previous_state',
        'new_state',
        'notes',
        'recorded_by_user_id',
    ];

    protected $casts = [
        'event_type' => EmploymentEventType::class,
        'occurred_on' => 'date',
        'previous_state' => 'array',
        'new_state' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    /**
     * Human-readable summary of what changed, safe for timelines/PDFs even
     * after the referenced dept/position rows are renamed or deleted.
     */
    public function getSummaryAttribute(): string
    {
        return match ($this->event_type) {
            EmploymentEventType::Hire => sprintf(
                'Hired as %s (%s)',
                $this->new_state['position_title'] ?? '—',
                $this->new_state['department_name'] ?? '—',
            ),
            EmploymentEventType::Promotion => sprintf(
                'Position: %s → %s',
                $this->previous_state['position_title'] ?? '—',
                $this->new_state['position_title'] ?? '—',
            ),
            EmploymentEventType::Transfer => sprintf(
                'Department: %s → %s',
                $this->previous_state['department_name'] ?? '—',
                $this->new_state['department_name'] ?? '—',
            ),
            EmploymentEventType::ContractChange => sprintf(
                'Contract: %s → %s',
                $this->previous_state['employment_type'] ?? '—',
                $this->new_state['employment_type'] ?? '—',
            ),
            EmploymentEventType::StatusChange => sprintf(
                'Status: %s → %s',
                $this->previous_state['employee_status'] ?? '—',
                $this->new_state['employee_status'] ?? '—',
            ),
            EmploymentEventType::Ended => sprintf(
                'Employment ended (%s)',
                $this->new_state['employee_status'] ?? '—',
            ),
            EmploymentEventType::Note => $this->notes ?? 'Note',
        };
    }
}
