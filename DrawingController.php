<?php

namespace App\Http\Controllers;

use App\Models\Drawing;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DrawingController extends Controller
{
    private function authorizeView(): void
    {
        abort_unless(auth()->user()?->hasPermission('drawings.view'), 403);
    }

    public function index(Request $request): View
    {
        $this->authorizeView();

        $filters = $request->only(['project_id', 'discipline', 'status']);

        $items = Drawing::query()
            ->with('project')
            ->when($request->filled('project_id'), fn ($q) => $q->where('project_id', $request->input('project_id')))
            ->when($request->filled('discipline'), fn ($q) => $q->where('discipline', $request->input('discipline')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderBy('discipline')->orderBy('order')->orderBy('code')
            ->get();

        $all = Drawing::all();
        $kpis = [
            'total' => $all->count(),
            'approved' => $all->where('status', 'approved')->count(),
            'under_review' => $all->where('status', 'under_review')->count(),
            'draft' => $all->where('status', 'draft')->count(),
        ];

        return view('drawings.index', [
            'grouped' => $items->groupBy('discipline'),
            'kpis' => $kpis,
            'projects' => Project::orderBy('name')->get(['id', 'name']),
            'disciplines' => Drawing::DISCIPLINES,
            'statuses' => Drawing::STATUSES,
            'filters' => $filters,
            'canEdit' => auth()->user()->hasPermission('drawings.edit'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->hasPermission('drawings.edit'), 403);

        $data = $this->validated($request);
        $data['order'] = (int) Drawing::where('project_id', $data['project_id'] ?? null)->max('order') + 10;

        Drawing::create($data);

        return back()->with('success', __('app.dwg_created'));
    }

    public function update(Request $request, Drawing $drawing): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        abort_unless(auth()->user()?->hasPermission('drawings.edit'), 403);

        $drawing->update($this->validated($request, true));

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'status' => $drawing->status,
                'status_class' => $drawing->statusClass(),
            ]);
        }

        return back()->with('success', __('app.dwg_updated'));
    }

    public function destroy(Drawing $drawing): RedirectResponse
    {
        abort_unless(auth()->user()?->hasPermission('drawings.edit'), 403);

        $drawing->delete();

        return back()->with('success', __('app.dwg_deleted'));
    }

    protected function validated(Request $request, bool $partial = false): array
    {
        $rule = fn (array $r) => $partial ? array_merge(['sometimes'], $r) : $r;

        return $request->validate([
            'project_id' => $rule(['nullable', 'exists:projects,id']),
            'code' => $rule(['required', 'string', 'max:40']),
            'title' => $rule(['required', 'string', 'max:255']),
            'discipline' => $rule(['required', 'in:'.implode(',', array_keys(Drawing::DISCIPLINES))]),
            'revision' => $rule(['nullable', 'string', 'max:10']),
            'drawing_date' => $rule(['nullable', 'date']),
            'status' => $rule(['required', 'in:'.implode(',', array_keys(Drawing::STATUSES))]),
            'note' => $rule(['nullable', 'string', 'max:500']),
        ]);
    }
}
