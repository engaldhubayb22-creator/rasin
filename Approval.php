<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Approval extends Model
{
    protected $fillable = [
        'project_id', 'doc', 'type', 'amount', 'submitted_by', 'submitted_by_id',
        'submitted_at', 'note', 'order',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'submitted_at' => 'date',
        ];
    }

    public const TYPES = [
        'purchase_request' => 'apv_type_pr',
        'purchase_order' => 'apv_type_po',
        'payment_certificate' => 'apv_type_ipc',
        'contract' => 'apv_type_contract',
        'other' => 'apv_type_other',
    ];

    public function typeLabel(): string
    {
        return __('app.'.(self::TYPES[$this->type] ?? 'apv_type_other'));
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ApprovalStep::class)->orderBy('order')->orderBy('id');
    }

    /** الخطوة الحالية المنتظرة (أول pending) */
    public function currentStep(): ?ApprovalStep
    {
        return $this->steps->firstWhere('status', 'pending');
    }

    public function approvedCount(): int
    {
        return $this->steps->where('status', 'approved')->count();
    }

    /** الحالة الكلية للمستند: completed/returned/rejected/in_progress */
    public function overallStatus(): string
    {
        if ($this->steps->contains('status', 'rejected')) {
            return 'rejected';
        }
        if ($this->steps->contains('status', 'returned')) {
            return 'returned';
        }
        if (! $this->steps->contains('status', 'pending') && $this->steps->count()) {
            return 'completed';
        }

        return 'in_progress';
    }

    public function overallStatusLabel(): string
    {
        return __('app.apv_'.$this->overallStatus());
    }

    /** لون chip للحالة (يوافق أصناف acn) */
    public function overallStatusClass(): string
    {
        return match ($this->overallStatus()) {
            'completed' => 's-completed',
            'returned' => 's-urgent',
            'rejected' => 's-cancelled',
            default => 's-in_progress',
        };
    }
}
