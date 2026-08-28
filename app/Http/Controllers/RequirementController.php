<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Requirement;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class RequirementController extends Controller
{
    /** صفحة متابعة المتطلبات — مجمّعة حسب المشروع + مؤشرات + فلاتر */
    public function index(Request $request): View
    {
        $filters = $request->only(['project_id', 'department', 'status', 'assigned_to']);

        $items = Requirement::query()
            ->with(['project', 'assignee'])
            ->when($request->filled('project_id'), fn ($q) => $q->where('project_id', $request->input('project_id')))
            ->when($request->filled('department'), fn ($q) => $q->where('department', $request->input('department')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('assigned_to'), fn ($q) => $q->where('assigned_to', $request->input('assigned_to')))
            ->orderBy('project_id')
            ->orderBy('order')
            ->get();

        $today = Carbon::today();
        $all = Requirement::all();
        $kpis = [
            'completed_week' => $all->where('status', 'completed')->filter(fn ($r) => $r->updated_at && $r->updated_at->greaterThanOrEqualTo($today->copy()->startOfWeek()))->count(),
            'in_progress' => $all->where('status', '!=', 'completed')->count(),
            'due_today' => $all->filter(fn ($r) => $r->isDueToday())->count(),
            'overdue' => $all->filter(fn ($r) => $r->isOverdue())->count(),
        ];

        return view('requirements.index', [
            'grouped' => $items->groupBy('project_id'),
            'kpis' => $kpis,
            'projects' => Project::orderBy('name')->get(['id', 'name']),
            'users' => User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'statuses' => Requirement::STATUSES,
            'departments' => Requirement::DEPARTMENTS,
            'filters' => $filters,
        ]);
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $data = $this->validated($request);
        $data['order'] = (int) $project->requirements()->max('order') + 10;

        $project->requirements()->create($data);

        return back()->with('success', __('app.req_created'));
    }

    public function update(Request $request, Requirement $requirement): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $requirement->update($this->validated($request, true));

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'status' => $requirement->status]);
        }

        return back()->with('success', __('app.req_updated'));
    }

    public function destroy(Requirement $requirement): RedirectResponse
    {
        $requirement->delete();

        return back()->with('success', __('app.req_deleted'));
    }

    protected function validated(Request $request, bool $partial = false): array
    {
        $rule = fn (array $r) => $partial ? array_merge(['sometimes'], $r) : $r;

        return $request->validate([
            'code' => $rule(['nullable', 'string', 'max:40']),
            'title' => $rule(['required', 'string', 'max:255']),
            'note' => $rule(['nullable', 'string', 'max:1000']),
            'department' => $rule(['nullable', 'in:'.implode(',', array_keys(Requirement::DEPARTMENTS))]),
            'assigned_to' => $rule(['nullable', 'exists:users,id']),
            'due_date' => $rule(['nullable', 'date']),
            'status' => $rule(['required', 'in:'.implode(',', array_keys(Requirement::STATUSES))]),
        ]);
    }
}
