<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('role');          // admin/executive/project_manager/engineer/finance
            $table->string('permission');    // module.action  (projects.view)
            $table->timestamps();

            $table->unique(['role', 'permission']);
            $table->index('role');
        });

        // ازرع التوزيعة الافتراضية من config/permissions.php (المدير يملك الكل ضمنياً)
        $now = now();
        $rows = [];
        foreach ((array) config('permissions.defaults', []) as $role => $perms) {
            foreach ($perms as $perm) {
                $rows[] = ['role' => $role, 'permission' => $perm, 'created_at' => $now, 'updated_at' => $now];
            }
        }
        if ($rows) {
            DB::table('role_permissions')->insertOrIgnore($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
