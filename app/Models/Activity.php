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
        'actual_percent', 'is_critical', 'status', 'level', 'order', 'notes',
        'pred1', 'type1', 'lag1', 'pred2', 'type2', 'lag2', 'pred3', 'type3', 'lag3',
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
            'level' => 'integer',
            'lag1' => 'integer',
            'lag2' => 'integer',
            'lag3' => 'integer',
        ];
    }

    public const STATUSES = [
        'not_started' => 'act_not_started',
        'in_progress' => 'act_in_progress',
        'completed' => 'act_completed',
        'delayed' => 'act_delayed',
    ];

    /** أنواع علاقات شبكة المنطق */
    public const REL_TYPES = ['FS', 'SS', 'FF', 'SF'];

    /** صنف لون الحالة (يوافق أصناف acn) */
    public function statusClass(): string
    {
        return match ($this->status) {
            'completed' => 's-completed',
            'in_progress' => 's-in_progress',
            'delayed' => 's-urgent',
            default => 's-pending',
        };
    }

    /** هل هذا صف مرحلة (عنوان) وليس نشاطاً؟ */
    public function isPhaseRow(): bool
    {
        return (int) $this->level === 0;
    }

    /** ملخص شبكة المنطق للعرض: "P10 FS · C20 SS+15" */
    public function predecessorsSummary(): string
    {
        $parts = [];
        foreach ([[$this->pred1, $this->type1, $this->lag1], [$this->pred2, $this->type2, $this->lag2], [$this->pred3, $this->type3, $this->lag3]] as [$p, $t, $l]) {
            if (! $p) {
                continue;
            }
            $lag = $l ? ($l > 0 ? '+'.$l : (string) $l) : '';
            $parts[] = trim($p.' '.($t ?: 'FS').$lag);
        }

        return implode(' · ', $parts);
    }

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
