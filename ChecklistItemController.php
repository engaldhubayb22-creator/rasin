<?php

namespace App\Http\Controllers;

use App\Models\ChecklistItem;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ChecklistItemController extends Controller
{
    /** مركز متابعة التشك لست — عبر كل المشاريع، مجمّع حسب المشروع */
    public function center(Request $request): View
    {
        $filters = $request->only(['project_id', 'phase', 'status', 'assigned_to']);

        $items = ChecklistItem::query()
            ->with(['project', 'assignee', 'approver'])
            ->when($request->filled('project_id'), fn ($q) => $q->where('project_id', $request->input('project_id')))
            ->when($request->filled('phase'), fn ($q) => $q->where('phase', $request->input('phase')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('assigned_to'), fn ($q) => $q->where('assigned_to', $request->input('assigned_to')))
            ->orderBy('project_id')->orderBy('order')
            ->get();

        $all = ChecklistItem::all();
        $applicable = $all->where('status', '!=', 'not_applicable');
        $done = $all->where('status', 'completed')->count();
        $kpis = [
            'pct' => $applicable->count() ? (int) round($done / $applicable->count() * 100) : 0,
            'done' => $done,
            'total' => $applicable->count(),
            'pending' => $all->where('status', 'pending_approval')->count(),
            'overdue' => $all->filter(fn ($i) => $i->isOverdue())->count(),
        ];

        return view('checklist.center', [
            'grouped' => $items->groupBy('project_id'),
            'kpis' => $kpis,
            'projects' => Project::orderBy('name')->get(['id', 'name']),
            'users' => User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'statuses' => ChecklistItem::STATUSES,
            'phases' => array_keys(config('checklist_template.phases', [])),
            'filters' => $filters,
        ]);
    }

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
