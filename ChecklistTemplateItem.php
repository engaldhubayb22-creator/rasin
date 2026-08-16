<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChecklistTemplateItem extends Model
{
    protected $fillable = ['phase', 'title', 'is_mandatory', 'order'];

    protected function casts(): array
    {
        return [
            'is_mandatory' => 'boolean',
        ];
    }

    /**
     * يزرع القالب من ملف الإعداد إن كان الجدول فارغاً.
     * يُستدعى قبل توليد التشك لست لأول مشروع.
     */
    public static function ensureSeeded(): void
    {
        if (static::query()->exists()) {
            return;
        }

        $order = 0;
        foreach (config('checklist_template.phases', []) as $phase => $items) {
            foreach ($items as [$title, $mandatory]) {
                $order += 10;
                static::create([
                    'phase' => $phase,
                    'title' => $title,
                    'is_mandatory' => (bool) $mandatory,
                    'order' => $order,
                ]);
            }
        }
    }
}
