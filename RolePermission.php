<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    protected $fillable = ['role', 'permission'];

    /** يزرع التوزيعة الافتراضية من config/permissions.php إن كان الجدول فارغاً */
    public static function ensureSeeded(): void
    {
        if (static::query()->exists()) {
            return;
        }

        foreach (config('permissions.defaults', []) as $role => $perms) {
            foreach ($perms as $perm) {
                static::firstOrCreate(['role' => $role, 'permission' => $perm]);
            }
        }
    }

    /** كل الصلاحيات الممكنة من الكتالوج (module.action) */
    public static function catalog(): array
    {
        $out = [];
        foreach (config('permissions.modules', []) as $module => $actions) {
            foreach ($actions as $action) {
                $out[] = $module.'.'.$action;
            }
        }

        return $out;
    }
}
