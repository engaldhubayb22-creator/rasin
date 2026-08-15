<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinanceController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->canSeeFinance(), 403);

        $projects = Project::with('budgetItems')->get();

        $totalCommitted = $projects->sum(fn ($p) => $p->totalCommitted());
        $totalActual = $projects->sum(fn ($p) => $p->totalActual());

        $fin = [
            'total_invoiced' => $totalCommitted,
            'total_paid' => $totalActual,
            'remaining_to_pay' => max($totalCommitted - $totalActual, 0),
            'payment_rate' => $totalCommitted > 0 ? (int) round($totalActual / $totalCommitted * 100) : 0,
        ];

        $projectsFinance = $projects->map(fn ($p) => [
            'name' => $p->name,
            'code' => $p->code,
            'budgeted' => $p->totalBudgeted(),
            'committed' => $p->totalCommitted(),
            'actual' => $p->totalActual(),
            'remaining' => $p->budgetRemaining(),
            'spent' => $p->budgetSpentPercent(),
            'over' => $p->isOverBudget(),
        ])->sortByDesc('budgeted')->values();

        return view('finance.index', compact('fin', 'projectsFinance'));
    }
}
