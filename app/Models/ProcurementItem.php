<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class ProcurementItem extends Model
{
    protected $fillable = [
        'project_id', 'item', 'activity_code', 'responsible',
        'need_by', 'select_by', 'note', 'order',
    ];

    protected function casts(): array
    {
        return [
            'need_by' => 'date',
            'select_by' => 'date',
        ];
    }

    /** الأيام المتبقية حتى آخر موعد لاختيار العينة (موجب = مازال في المستقبل) */
    public function daysLeft(): int
    {
        if (! $this->select_by) {
            return 0;
        }

        // حساب بالطوابع الزمنية — مستقل عن اختلاف إصدارات Carbon (2/3)
        $target = Carbon::parse($this->select_by)->startOfDay()->getTimestamp();
        $today = Carbon::today()->getTimestamp();

        return intdiv($target - $today, 86400);
    }

    /** مستوى التنبيه: overdue/critical/warning/on_plan */
    public function alertLevel(): string
    {
        $left = $this->daysLeft();

        return match (true) {
            $left < 0 => 'overdue',
            $left <= 14 => 'critical',
            $left <= 30 => 'warning',
            default => 'on_plan',
        };
    }

    public function alertLabel(): string
    {
        return __('app.proc_'.$this->alertLevel());
    }

    /** لون chip (أصناف acn) */
    public function alertClass(): string
    {
        return match ($this->alertLevel()) {
            'overdue', 'critical' => 's-cancelled',
            'warning' => 's-urgent',
            default => 's-completed',
        };
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
