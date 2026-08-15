<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ScheduleActivity;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportsController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->canSeeReports(), 403);

        $projects = Project::with(['budgetItems', 'projectManager'])->get();

        // ١) المشاريع المنجزة
        $completed = $projects->where('status', 'completed')->values();

        // ٢) حالة المشاريع النشطة
        $active = $projects->where('status', 'active')->sortByDesc('progress')->values();

        // ٣) ملخّص مالي لكل مشروع
        $finance = $projects->map(fn ($p) => [
            'name' => $p->name,
            'code' => $p->code,
            'status' => $p->statusLabel(),
            'statusColor' => $p->statusColor(),
            'budgeted' => $p->totalBudgeted(),
            'actual' => $p->totalActual(),
            'remaining' => $p->budgetRemaining(),
            'spent' => $p->budgetSpentPercent(),
            'over' => $p->isOverBudget(),
        ])->sortByDesc('budgeted')->values();

        // ٤) الأنشطة المتأخرة (من نسخ الجداول الزمنية)
        $delayed = ScheduleActivity::with('version.project')
            ->where('level', '>=', 2)
            ->where(fn ($q) => $q->where('status', 'delayed')->orWhere('delay_days', '>', 0))
            ->orderByDesc('delay_days')
            ->take(50)
            ->get();

        return view('reports.index', compact('completed', 'active', 'finance', 'delayed'));
    }
}
