<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleActivity extends Model
{
    protected $fillable = [
        'schedule_version_id', 'wbs', 'level', 'name', 'name_en',
        'planned_start', 'planned_finish', 'actual_start', 'actual_finish',
        'percent', 'is_critical', 'delay_days', 'status', 'order',
    ];

    protected function casts(): array
    {
        return [
            'planned_start' => 'date',
            'planned_finish' => 'date',
            'actual_start' => 'date',
            'actual_finish' => 'date',
            'percent' => 'integer',
            'is_critical' => 'boolean',
            'delay_days' => 'integer',
            'level' => 'integer',
        ];
    }

    public const STATUSES = [
        'not_started' => 'act_not_started',
        'in_progress' => 'act_in_progress',
        'completed' => 'act_completed',
        'delayed' => 'act_delayed',
    ];

    public function isPhase(): bool
    {
        return $this->level <= 1;
    }

    public function isLeaf(): bool
    {
        return $this->level >= 2;
    }

    public function statusLabel(): string
    {
        return __('app.'.(self::STATUSES[$this->status] ?? $this->status));
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'completed' => 'emerald',
            'in_progress' => 'sky',
            'delayed' => 'rose',
            default => 'slate',
        };
    }

    public function displayName(): string
    {
        if (app()->getLocale() === 'en' && filled($this->name_en)) {
            return $this->name_en;
        }

        return $this->name;
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(ScheduleVersion::class, 'schedule_version_id');
    }
}
