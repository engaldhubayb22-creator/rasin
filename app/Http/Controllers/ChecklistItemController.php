<?php

namespace App\Http\Controllers;

use App\Models\ChecklistItem;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ChecklistItemController extends Controller
{
    /** توليد / إعادة توليد التشك لست من القالب */
    public function generate(Request $request, Project $project): RedirectResponse
    {
        $reset = $request->boolean('reset');
        $count = $project->generateChecklist($reset);

        return redirect()
            ->route('projects.show', $project)->withFragment('checklist')
            ->with('success', __('app.cl_generated', ['count' => $count]));
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $data = $this->validated($request);
        $data['order'] = (int) $project->checklistItems()->max('order') + 10;

        $project->checklistItems()->create($data);

        return back()->with('success', __('app.cl_created'));
    }

    public function update(Request $request, ChecklistItem $checklistItem): RedirectResponse
    {
        $data = $this->validated($request, true);
        $data['is_mandatory'] = $request->has('is_mandatory') ? $request->boolean('is_mandatory') : $checklistItem->is_mandatory;

        // عند الاكتمال، سجّل تاريخ الإنجاز الفعلي تلقائياً إن لم يوجد
        if (($data['status'] ?? null) === 'completed' && ! $checklistItem->actual_date && empty($data['actual_date'])) {
            $data['actual_date'] = now()->toDateString();
        }

        $checklistItem->update($data);

        return back()->with('success', __('app.cl_updated'));
    }

    public function destroy(ChecklistItem $checklistItem): RedirectResponse
    {
        $checklistItem->delete();

        return back()->with('success', __('app.cl_deleted'));
    }

    protected function validated(Request $request, bool $partial = false): array
    {
        $rule = fn (array $r) => $partial ? array_merge(['sometimes'], $r) : $r;

        return $request->validate([
            'phase' => $rule(['nullable', 'string', 'max:120']),
            'title' => $rule(['required', 'string', 'max:255']),
            'is_mandatory' => $rule(['nullable', 'boolean']),
            'assigned_to' => $rule(['nullable', 'exists:users,id']),
            'planned_date' => $rule(['nullable', 'date']),
            'actual_date' => $rule(['nullable', 'date']),
            'status' => $rule(['required', 'in:'.implode(',', array_keys(ChecklistItem::STATUSES))]),
            'evidence' => $rule(['nullable', 'string', 'max:255']),
            'approved_by' => $rule(['nullable', 'exists:users,id']),
            'notes' => $rule(['nullable', 'string', 'max:1000']),
        ]);
    }
}
