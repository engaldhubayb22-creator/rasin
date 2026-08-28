<?php

namespace App\Http\Controllers;

use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    private function authorizeManage(): void
    {
        abort_unless(auth()->user()?->hasPermission('users.manage'), 403);
    }

    public function index(): View
    {
        $this->authorizeManage();

        RolePermission::ensureSeeded();

        // مصفوفة الأدوار × الصلاحيات
        $rolePerms = RolePermission::all()->groupBy('role')->map->pluck('permission')->map->all()->all();

        return view('admin.users', [
            'users' => User::orderBy('name')->get(),
            'roles' => User::roles(),
            'modules' => config('permissions.modules', []),
            'rolePerms' => $rolePerms,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManage();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'role' => ['required', Rule::in(array_keys(User::roles()))],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        User::create([
            'company_id' => auth()->user()->company_id,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'job_title' => $data['job_title'] ?? null,
            'role' => $data['role'],
            'password' => Hash::make($data['password'] ?? 'Rasine#2026'),
            'is_active' => true,
        ]);

        return back()->with('success', __('app.usr_created'));
    }

    public function update(Request $request, User $user): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $this->authorizeManage();

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'role' => ['sometimes', 'required', Rule::in(array_keys(User::roles()))],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        if ($request->has('is_active')) {
            $data['is_active'] = $request->boolean('is_active');
        }

        $user->update($data);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'is_active' => $user->is_active]);
        }

        return back()->with('success', __('app.usr_updated'));
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorizeManage();

        abort_if($user->id === auth()->id(), 403);

        $user->delete();

        return back()->with('success', __('app.usr_deleted'));
    }
}
