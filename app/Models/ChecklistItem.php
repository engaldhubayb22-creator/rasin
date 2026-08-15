<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class ChecklistItem extends Model
{
    protected $fillable = [
        'project_id', 'phase', 'title', 'is_mandatory', 'assigned_to',
        'planned_date', 'actual_date', 'status', 'evidence', 'approved_by', 'notes', 'order',
    ];

    protected function casts(): array
    {
        return [
            'is_mandatory' => 'boolean',
            'planned_date' => 'date',
            'actual_date' => 'date',
        ];
    }

    public const STATUSES = [
        'not_started' => 'cl_not_started',
        'in_progress' => 'cl_in_progress',
        'pending_approval' => 'cl_pending_approval',
        'completed' => 'cl_completed',
        'not_applicable' => 'cl_not_applicable',
    ];

    public function statusLabel(): string
    {
        return __('app.'.(self::STATUSES[$this->status] ?? $this->status));
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'completed' => 'emerald',
            'in_progress' => 'sky',
            'pending_approval' => 'amber',
            'not_applicable' => 'slate',
            default => 'slate',
        };
    }

    /** صنف لون الحالة بنمط أكونكس */
    public function statusClass(): string
    {
        return match ($this->status) {
            'completed' => 's-completed',
            'in_progress' => 's-in_progress',
            'pending_approval' => 's-urgent',
            'not_applicable' => 's-cancelled',
            default => 's-pending',
        };
    }

    public function isDone(): bool
    {
        return $this->status === 'completed';
    }

    /** متأخر: له تاريخ مخطط مضى ولم يكتمل (ولا يُستثنى) */
    public function isOverdue(): bool
    {
        return $this->planned_date
            && ! in_array($this->status, ['completed', 'not_applicable'], true)
            && $this->planned_date->isBefore(Carbon::today());
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
