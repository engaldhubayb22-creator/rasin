<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ApprovalController extends Controller
{
    private function authorizeView(): void
    {
        abort_unless(auth()->user()?->hasPermission('approvals.view'), 403);
    }

    public function index(Request $request): View
    {
        $this->authorizeView();

        $filters = $request->only(['project_id', 'type']);

        $items = Approval::query()
            ->with(['project', 'steps'])
            ->when($request->filled('project_id'), fn ($q) => $q->where('project_id', $request->input('project_id')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->orderByDesc('submitted_at')->orderByDesc('id')
            ->get();

        $all = Approval::with('steps')->get();
        $kpis = [
            'total' => $all->count(),
            'pending' => $all->filter(fn ($a) => $a->overallStatus() === 'in_progress')->count(),
            'returned' => $all->filter(fn ($a) => in_array($a->overallStatus(), ['returned', 'rejected']))->count(),
            'completed' => $all->filter(fn ($a) => $a->overallStatus() === 'completed')->count(),
            'amount_pending' => $all->filter(fn ($a) => $a->overallStatus() === 'in_progress')->sum('amount'),
        ];

        return view('approvals.index', [
            'items' => $items,
            'kpis' => $kpis,
            'projects' => Project::orderBy('name')->get(['id', 'name']),
            'types' => Approval::TYPES,
            'filters' => $filters,
            'canApprove' => auth()->user()->hasPermission('approvals.approve'),
            'canManage' => auth()->user()->hasPermission('approvals.approve'),
            'users' => User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /** اتخاذ قرار على الخطوة الحالية: approved/returned/rejected */
    public function act(Request $request, Approval $approval): RedirectResponse
    {
        abort_unless(auth()->user()?->hasPermission('approvals.approve'), 403);

        $decision = $request->validate([
            'decision' => ['required', 'in:approved,returned,rejected'],
        ])['decision'];

        $step = $approval->currentStep();
        if (! $step) {
            return back()->with('success', __('app.apv_no_action'));
        }

        $step->update([
            'status' => $decision,
            'decided_at' => Carbon::today(),
            'approver_id' => auth()->id(),
        ]);

        // عند الإعادة أو الرفض، أعِد الخطوات التالية إلى الانتظار
        if ($decision !== 'approved') {
            $approval->steps()->where('order', '>', $step->order)->update([
                'status' => 'pending',
                'decided_at' => null,
            ]);
        }

        return back()->with('success', __('app.apv_decision_saved'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->hasPermission('approvals.approve'), 403);

        $data = $request->validate([
            'doc' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:'.implode(',', array_keys(Approval::TYPES))],
            'project_id' => ['nullable', 'exists:projects,id'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['nullable', 'string', 'max:120'],
            'approvers' => ['nullable', 'array'],
            'approvers.*' => ['nullable', 'string', 'max:120'],
        ]);

        $approval = Approval::create([
            'doc' => $data['doc'],
            'type' => $data['type'],
            'project_id' => $data['project_id'] ?? null,
            'amount' => $data['amount'] ?? null,
            'submitted_by' => auth()->user()->name,
            'submitted_by_id' => auth()->id(),
            'submitted_at' => Carbon::today(),
        ]);

        $order = 0;
        foreach ($data['roles'] as $i => $roleLabel) {
            if (! trim((string) $roleLabel)) {
                continue;
            }
            $order += 10;
            $approval->steps()->create([
                'role_label' => $roleLabel,
                'approver_name' => $data['approvers'][$i] ?? null,
                'status' => 'pending',
                'order' => $order,
            ]);
        }

        return back()->with('success', __('app.apv_created'));
    }

    public function destroy(Approval $approval): RedirectResponse
    {
        abort_unless(auth()->user()?->hasPermission('approvals.approve'), 403);

        $approval->delete();

        return back()->with('success', __('app.apv_deleted'));
    }
}
