<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id', 'item_code', 'category', 'name', 'name_en',
        'budgeted_amount', 'committed_amount', 'actual_amount', 'order', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'budgeted_amount' => 'decimal:2',
            'committed_amount' => 'decimal:2',
            'actual_amount' => 'decimal:2',
        ];
    }

    /** المتبقي = المعتمد − المصروف (سالب يعني تجاوز) */
    public function remaining(): float
    {
        return (float) $this->budgeted_amount - (float) $this->actual_amount;
    }

    /** الانحراف = المعتمد − المرتبط (سالب يعني ارتباط يفوق المعتمد) */
    public function variance(): float
    {
        return (float) $this->budgeted_amount - (float) $this->committed_amount;
    }

    /** نسبة الصرف من المعتمد */
    public function spentPercent(): int
    {
        $budget = (float) $this->budgeted_amount;

        if ($budget <= 0) {
            return (float) $this->actual_amount > 0 ? 100 : 0;
        }

        return (int) round(((float) $this->actual_amount / $budget) * 100);
    }

    /** تجاوز الميزانية؟ */
    public function isOverBudget(): bool
    {
        return (float) $this->actual_amount > (float) $this->budgeted_amount;
    }

    /** لون حالة الصرف */
    public function healthColor(): string
    {
        if ($this->isOverBudget()) {
            return 'rose';
        }

        return $this->spentPercent() >= 85 ? 'amber' : 'emerald';
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
