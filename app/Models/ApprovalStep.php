<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalStep extends Model
{
    protected $fillable = [
        'approval_id', 'role_label', 'approver_name', 'approver_id',
        'status', 'decided_at', 'order',
    ];

    protected function casts(): array
    {
        return [
            'decided_at' => 'date',
        ];
    }

    public const STATUSES = [
        'pending' => 'apv_pending',
        'approved' => 'apv_approved',
        'returned' => 'apv_returned',
        'rejected' => 'apv_rejected',
    ];

    public function statusLabel(): string
    {
        return __('app.'.(self::STATUSES[$this->status] ?? 'apv_pending'));
    }

    public function statusIcon(): string
    {
        return match ($this->status) {
            'approved' => '✓',
            'rejected' => '✕',
            'returned' => '↺',
            default => '•',
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'approved' => '#2f7d4f',
            'rejected' => '#c0392b',
            'returned' => '#c96a1f',
            default => '#a8b6c6',
        };
    }

    public function approval(): BelongsTo
    {
        return $this->belongsTo(Approval::class);
    }
}
