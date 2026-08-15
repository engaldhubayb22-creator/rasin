<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id', 'project_id', 'title', 'description',
        'assigned_to', 'status', 'priority', 'progress',
        'due_date', 'completed_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed_at' => 'datetime',
            'progress' => 'integer',
        ];
    }

    public const STATUSES = [
        'pending' => 'task_pending',
        'in_progress' => 'task_in_progress',
        'completed' => 'task_completed',
        'blocked' => 'task_blocked',
    ];

    public const PRIORITIES = [
        'low' => 'priority_low',
        'normal' => 'priority_normal',
        'high' => 'priority_high',
        'urgent' => 'priority_urgent',
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
            'blocked' => 'rose',
            default => 'slate',
        };
    }

    public function priorityLabel(): string
    {
        return __('app.'.(self::PRIORITIES[$this->priority] ?? $this->priority));
    }

    public function priorityColor(): string
    {
        return match ($this->priority) {
            'urgent' => 'rose',
            'high' => 'amber',
            'low' => 'slate',
            default => 'sky',
        };
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
