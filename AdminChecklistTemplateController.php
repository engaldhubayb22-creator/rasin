<?php

namespace App\Http\Controllers;

use App\Models\ChecklistTemplateItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminChecklistTemplateController extends Controller
{
    /** يقصر الوصول على المدير فقط */
    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    /** صفحة الإدارة — قالب التشك لست الموحّد */
    public function index(): View
    {
        $this->authorizeAdmin();

        // ازرع القالب من ملف الإعداد لو كان الجدول فارغاً (أول مرة)
        ChecklistTemplateItem::ensureSeeded();

        $items = ChecklistTemplateItem::orderBy('order')->orderBy('id')->get();

        return view('admin.checklist_template', [
            'grouped' => $items->groupBy('phase'),
            'total' => $items->count(),
            'phases' => $items->pluck('phase')->unique()->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'phase' => ['required', 'string', 'max:120'],
            'title' => ['required', 'string', 'max:255'],
            'is_mandatory' => ['nullable', 'boolean'],
        ]);
        $data['is_mandatory'] = $request->boolean('is_mandatory');
        $data['order'] = (int) ChecklistTemplateItem::max('order') + 10;

        ChecklistTemplateItem::create($data);

        return back()->with('success', __('app.tpl_created'));
    }

    public function update(Request $request, ChecklistTemplateItem $templateItem): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'phase' => ['sometimes', 'required', 'string', 'max:120'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'is_mandatory' => ['sometimes', 'nullable', 'boolean'],
        ]);
        if ($request->has('is_mandatory')) {
            $data['is_mandatory'] = $request->boolean('is_mandatory');
        }

        $templateItem->update($data);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'is_mandatory' => $templateItem->is_mandatory]);
        }

        return back()->with('success', __('app.tpl_updated'));
    }

    public function destroy(ChecklistTemplateItem $templateItem): RedirectResponse
    {
        $this->authorizeAdmin();

        $templateItem->delete();

        return back()->with('success', __('app.tpl_deleted'));
    }

    /** إعادة تعيين القالب من ملف الإعداد الأصلي */
    public function reset(): RedirectResponse
    {
        $this->authorizeAdmin();

        ChecklistTemplateItem::query()->delete();
        ChecklistTemplateItem::ensureSeeded();

        return back()->with('success', __('app.tpl_reset_done'));
    }
}
