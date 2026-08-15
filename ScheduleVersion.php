<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class ScheduleVersion extends Model
{
    protected $fillable = [
        'project_id', 'name', 'status', 'period_start', 'period_finish',
        'source_file', 'uploaded_by', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_finish' => 'date',
        ];
    }

    public const STATUSES = [
        'pending' => 'sv_pending',
        'approved' => 'sv_approved',
        'rejected' => 'sv_rejected',
    ];

    public function statusLabel(): string
    {
        return __('app.'.(self::STATUSES[$this->status] ?? $this->status));
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'approved' => 'emerald',
            'rejected' => 'rose',
            default => 'amber',
        };
    }

    // ===== العلاقات =====
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ScheduleActivity::class)->orderBy('order')->orderBy('id');
    }

    // ===== مؤشرات محسوبة (من الأنشطة الورقية level=2) =====
    public function leaves()
    {
        return $this->activities->where('level', '>=', 2);
    }

    public function phasesList()
    {
        return $this->activities->where('level', 1);
    }

    public function overallPercent(): float
    {
        $leaves = $this->leaves();

        return $leaves->count() ? round($leaves->avg('percent'), 2) : 0;
    }

    public function totalActivities(): int
    {
        return $this->leaves()->count();
    }

    public function phasesCount(): int
    {
        return $this->phasesList()->count();
    }

    public function criticalCount(): int
    {
        return $this->leaves()->where('is_critical', true)->count();
    }

    public function delayedCount(): int
    {
        return $this->leaves()->filter(fn ($a) => $a->status === 'delayed' || $a->delay_days > 0)->count();
    }

    /** أنشطة قادمة خلال 14 يوم ولم تبدأ */
    public function upcomingCount(?Carbon $today = null): int
    {
        $today = $today ?: Carbon::today();
        $limit = $today->copy()->addDays(14);

        return $this->leaves()->filter(function ($a) use ($today, $limit) {
            return $a->status === 'not_started'
                && $a->planned_start
                && $a->planned_start->betweenIncluded($today, $limit);
        })->count();
    }

    /** تنبيهات: أنشطة حرجة مهملة (حرجة + 0% + متأخرة/لم تبدأ) */
    public function alerts()
    {
        return $this->leaves()
            ->filter(fn ($a) => $a->is_critical && $a->percent === 0 && in_array($a->status, ['delayed', 'not_started'], true))
            ->values();
    }
}
