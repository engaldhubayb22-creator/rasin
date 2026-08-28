<?php

namespace App\Http\Controllers;

use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RolePermissionController extends Controller
{
    private function authorizeManage(): void
    {
        abort_unless(auth()->user()?->hasPermission('users.manage'), 403);
    }

    /** تبديل صلاحية لدور (تشغيل/إيقاف) — عبر AJAX */
    public function toggle(Request $request): JsonResponse|RedirectResponse
    {
        $this->authorizeManage();

        $data = $request->validate([
            'role' => ['required', Rule::in(array_keys(User::roles()))],
            'permission' => ['required', 'in:'.implode(',', RolePermission::catalog())],
            'granted' => ['required', 'boolean'],
        ]);

        // دور المدير يملك كل الصلاحيات دائماً — لا يُعدّل
        if ($data['role'] === User::ROLE_ADMIN) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => true, 'locked' => true]);
            }

            return back();
        }

        if ($request->boolean('granted')) {
            RolePermission::firstOrCreate(['role' => $data['role'], 'permission' => $data['permission']]);
        } else {
            RolePermission::where('role', $data['role'])->where('permission', $data['permission'])->delete();
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', __('app.saved'));
    }
}
