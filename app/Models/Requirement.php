<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Requirement extends Model
{
    protected $fillable = [
        'project_id', 'code', 'title', 'note', 'department',
        'assigned_to', 'due_date', 'status', 'order',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
        ];
    }

    public const STATUSES = [
        'urgent' => 'req_urgent',
        'in_progress' => 'req_in_progress',
        'pending' => 'req_pending',
        'completed' => 'req_completed',
    ];

    public const DEPARTMENTS = [
        'projects_mgmt' => 'dept_projects',
        'procurement' => 'dept_procurement',
        'executive' => 'dept_executive',
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
            'urgent' => 'rose',
            default => 'slate', // pending
        };
    }

    public function departmentLabel(): string
    {
        return $this->department ? __('app.'.(self::DEPARTMENTS[$this->department] ?? $this->department)) : '—';
    }

    public function isOverdue(): bool
    {
        return $this->due_date
            && $this->status !== 'completed'
            && $this->due_date->isBefore(Carbon::today());
    }

    public function isDueToday(): bool
    {
        return $this->due_date
            && $this->status !== 'completed'
            && $this->due_date->isSameDay(Carbon::today());
    }

    // النطاقات
    public function scopeOpen(Builder $q): Builder
    {
        return $q->where('status', '!=', 'completed');
    }

    // العلاقات
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
