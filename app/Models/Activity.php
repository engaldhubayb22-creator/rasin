<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id', 'activity_code', 'phase', 'name', 'name_en',
        'duration_days', 'planned_start', 'planned_finish',
        'actual_start', 'actual_finish', 'planned_percent',
        'actual_percent', 'is_critical', 'status', 'order', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'planned_start' => 'date',
            'planned_finish' => 'date',
            'actual_start' => 'date',
            'actual_finish' => 'date',
            'planned_percent' => 'integer',
            'actual_percent' => 'integer',
            'is_critical' => 'boolean',
        ];
    }

    public const STATUSES = [
        'not_started' => 'act_not_started',
        'in_progress' => 'act_in_progress',
        'completed' => 'act_completed',
        'delayed' => 'act_delayed',
    ];

    /** الانحراف = الفعلي - المخطط (سالب يعني تأخّر) */
    public function variance(): int
    {
        return (int) $this->actual_percent - (int) $this->planned_percent;
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

    /** لون الانحراف */
    public function varianceColor(): string
    {
        $v = $this->variance();

        return $v < 0 ? 'rose' : ($v > 0 ? 'emerald' : 'slate');
    }

    /** الاسم حسب لغة العرض */
    public function displayName(): string
    {
        if (app()->getLocale() === 'en' && filled($this->name_en)) {
            return $this->name_en;
        }

        return $this->name;
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
