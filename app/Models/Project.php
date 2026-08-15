<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'type',
        'description',
        'project_manager_id',
        'supervisor_id',
        'client_name',
        'location',
        'budget',
        'contract_value',
        'start_date',
        'end_date',
        'progress',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'budget' => 'decimal:2',
            'contract_value' => 'decimal:2',
            'progress' => 'integer',
        ];
    }

    // حالات المشروع
    public const STATUSES = [
        'active' => 'قيد التنفيذ',
        'on_hold' => 'متوقف مؤقتاً',
        'completed' => 'مكتمل',
        'cancelled' => 'ملغي',
    ];

    // أنواع المشروع (تحدّد قالب التشك لست)
    public const TYPES = [
        'tower' => 'type_tower',
        'villa' => 'type_villa',
        'finishing' => 'type_finishing',
        'mosque' => 'type_mosque',
        'infrastructure' => 'type_infrastructure',
    ];

    public function typeLabel(): string
    {
        return $this->type ? __('app.'.(self::TYPES[$this->type] ?? $this->type)) : '—';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'active' => __('app.status_active'),
            'on_hold' => __('app.status_on_hold'),
            'completed' => __('app.status_completed'),
            'cancelled' => __('app.status_cancelled'),
            default => $this->status,
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'active' => 'emerald',
            'on_hold' => 'amber',
            'completed' => 'sky',
            'cancelled' => 'rose',
            default => 'slate',
        };
    }

    // العلاقات
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function projectManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'project_manager_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class)->orderBy('order')->orderBy('id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function budgetItems(): HasMany
    {
        return $this->hasMany(BudgetItem::class)->orderBy('order')->orderBy('id');
    }

    public function scheduleVersions(): HasMany
    {
        return $this->hasMany(ScheduleVersion::class)->latest();
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(Requirement::class)->orderBy('order')->orderBy('id');
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(ChecklistItem::class)->orderBy('order')->orderBy('id');
    }

    /** توليد بنود التشك لست من القالب الموحّد (لا يكرّر لو موجودة) */
    public function generateChecklist(bool $reset = false): int
    {
        if ($reset) {
            $this->checklistItems()->delete();
        } elseif ($this->checklistItems()->exists()) {
            return 0;
        }

        $order = 0;
        $created = 0;
        foreach (config('checklist_template.phases', []) as $phase => $items) {
            foreach ($items as [$title, $mandatory]) {
                $order += 10;
                $this->checklistItems()->create([
                    'phase' => $phase,
                    'title' => $title,
                    'is_mandatory' => $mandatory,
                    'status' => 'not_started',
                    'order' => $order,
                ]);
                $created++;
            }
        }

        return $created;
    }

    // إحصائيات التشك لست (تستبعد "لا ينطبق")
    public function checklistTotal(): int
    {
        return $this->checklistItems->where('status', '!=', 'not_applicable')->count();
    }

    public function checklistDone(): int
    {
        return $this->checklistItems->where('status', 'completed')->count();
    }

    public function checklistPercent(): int
    {
        $total = $this->checklistTotal();

        return $total ? (int) round($this->checklistDone() / $total * 100) : 0;
    }

    // ===== إجماليات الميزانية =====

    /** إجمالي المعتمد من بنود الميزانية (يرجع لميزانية المشروع إن لم توجد بنود) */
    public function totalBudgeted(): float
    {
        $sum = (float) $this->budgetItems->sum('budgeted_amount');

        return $sum > 0 ? $sum : (float) $this->budget;
    }

    public function totalCommitted(): float
    {
        return (float) $this->budgetItems->sum('committed_amount');
    }

    public function totalActual(): float
    {
        return (float) $this->budgetItems->sum('actual_amount');
    }

    public function budgetRemaining(): float
    {
        return $this->totalBudgeted() - $this->totalActual();
    }

    /** نسبة الصرف من إجمالي المعتمد */
    public function budgetSpentPercent(): int
    {
        $budget = $this->totalBudgeted();

        if ($budget <= 0) {
            return 0;
        }

        return (int) round(($this->totalActual() / $budget) * 100);
    }

    public function isOverBudget(): bool
    {
        return $this->totalActual() > $this->totalBudgeted();
    }

    public function teamMembers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_members')
            ->withPivot(['team_role', 'is_primary'])
            ->withTimestamps();
    }

    // النطاقات (Scopes)
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%")
                ->orWhere('client_name', 'like', "%{$term}%")
                ->orWhere('location', 'like', "%{$term}%");
        });
    }
}
