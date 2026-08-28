<?php

namespace App\Http\Controllers;

use App\Models\ProcurementItem;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProcurementController extends Controller
{
    private function authorizeView(): void
    {
        abort_unless(auth()->user()?->hasPermission('procurement.view'), 403);
    }

    public function index(Request $request): View
    {
        $this->authorizeView();

        $filters = $request->only(['project_id']);

        $items = ProcurementItem::query()
            ->with('project')
            ->when($request->filled('project_id'), fn ($q) => $q->where('project_id', $request->input('project_id')))
            ->orderBy('select_by')
            ->get();

        $kpis = [
            'total' => $items->count(),
            'overdue' => $items->filter(fn ($i) => $i->alertLevel() === 'overdue')->count(),
            'critical' => $items->filter(fn ($i) => $i->alertLevel() === 'critical')->count(),
            'on_plan' => $items->filter(fn ($i) => $i->alertLevel() === 'on_plan')->count(),
        ];

        return view('procurement.index', [
            'items' => $items,
            'kpis' => $kpis,
            'projects' => Project::orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
            'canEdit' => auth()->user()->hasPermission('procurement.edit'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->hasPermission('procurement.edit'), 403);

        $data = $this->validated($request);
        $data['order'] = (int) ProcurementItem::max('order') + 10;

        ProcurementItem::create($data);

        return back()->with('success', __('app.proc_created'));
    }

    public function update(Request $request, ProcurementItem $procurementItem): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        abort_unless(auth()->user()?->hasPermission('procurement.edit'), 403);

        $procurementItem->update($this->validated($request, true));

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'days_left' => $procurementItem->daysLeft(),
                'alert' => $procurementItem->alertLabel(),
                'alert_class' => $procurementItem->alertClass(),
            ]);
        }

        return back()->with('success', __('app.proc_updated'));
    }

    public function destroy(ProcurementItem $procurementItem): RedirectResponse
    {
        abort_unless(auth()->user()?->hasPermission('procurement.edit'), 403);

        $procurementItem->delete();

        return back()->with('success', __('app.proc_deleted'));
    }

    protected function validated(Request $request, bool $partial = false): array
    {
        $rule = fn (array $r) => $partial ? array_merge(['sometimes'], $r) : $r;

        return $request->validate([
            'project_id' => $rule(['nullable', 'exists:projects,id']),
            'item' => $rule(['required', 'string', 'max:255']),
            'activity_code' => $rule(['nullable', 'string', 'max:20']),
            'responsible' => $rule(['nullable', 'string', 'max:120']),
            'need_by' => $rule(['required', 'date']),
            'select_by' => $rule(['required', 'date']),
            'note' => $rule(['nullable', 'string', 'max:500']),
        ]);
    }
}
