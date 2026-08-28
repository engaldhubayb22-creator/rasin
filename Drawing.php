<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Drawing extends Model
{
    protected $fillable = [
        'project_id', 'code', 'title', 'discipline', 'revision',
        'drawing_date', 'status', 'note', 'order',
    ];

    protected function casts(): array
    {
        return [
            'drawing_date' => 'date',
        ];
    }

    public const DISCIPLINES = [
        'architectural' => 'disc_architectural',
        'structural' => 'disc_structural',
        'mep' => 'disc_mep',
        'infrastructure' => 'disc_infrastructure',
        'landscape' => 'disc_landscape',
        'other' => 'disc_other',
    ];

    public const STATUSES = [
        'draft' => 'dwg_draft',
        'under_review' => 'dwg_under_review',
        'approved' => 'dwg_approved',
    ];

    public function disciplineLabel(): string
    {
        return __('app.'.(self::DISCIPLINES[$this->discipline] ?? 'disc_other'));
    }

    public function statusLabel(): string
    {
        return __('app.'.(self::STATUSES[$this->status] ?? 'dwg_draft'));
    }

    public function statusClass(): string
    {
        return match ($this->status) {
            'approved' => 's-completed',
            'under_review' => 's-urgent',
            default => 's-pending',
        };
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
