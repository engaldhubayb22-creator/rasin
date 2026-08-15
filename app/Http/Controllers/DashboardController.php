<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $projects = Project::with(['activities', 'budgetItems', 'projectManager'])->get();

        // ===== مؤشرات مالية =====
        $activeProjects = $projects->where('status', 'active');
        $activeValue = (float) $activeProjects->sum('contract_value');

        $totalBudgeted = $projects->sum(fn ($p) => $p->totalBudgeted());
        $totalCommitted = $projects->sum(fn ($p) => $p->totalCommitted());
        $totalActual = $projects->sum(fn ($p) => $p->totalActual());
        // الالتزامات المالية = عقود + ما تم ارتباطه (أوامر شراء/عقود من الميزانية)
        $commitments = (float) $projects->sum('contract_value') + $totalCommitted;
        $remainingToPay = max($totalCommitted - $totalActual, 0);
        $paymentRate = $totalCommitted > 0 ? (int) round($totalActual / $totalCommitted * 100) : 0;

        // ===== صحة المحفظة (من انحراف الأنشطة لكل مشروع نشط) =====
        $health = ['on_track' => 0, 'slight' => 0, 'at_risk' => 0, 'critical' => 0];
        foreach ($activeProjects as $p) {
            $acts = $p->activities;
            $v = $acts->count() ? (int) round($acts->avg(fn ($a) => $a->variance())) : 0;
            if ($v >= 0) {
                $health['on_track']++;
            } elseif ($v >= -15) {
                $health['slight']++;
            } elseif ($v >= -30) {
                $health['at_risk']++;
            } else {
                $health['critical']++;
            }
        }

        // ===== أكبر العملاء =====
        $topClients = $projects
            ->filter(fn ($p) => filled($p->client_name))
            ->groupBy('client_name')
            ->map(fn ($grp, $name) => [
                'name' => $name,
                'projects' => $grp->count(),
                'value' => (float) $grp->sum('contract_value'),
            ])
            ->sortByDesc('value')
            ->take(5)
            ->values();

        // ===== أكبر الموردين (لا توجد وحدة مشتريات بعد) =====
        $topVendors = collect();

        // ===== الاتجاه الشهري (آخر 6 أشهر) — قيمة العقود حسب شهر البدء =====
        $now = Carbon::parse($request->attributes->get('today') ?? now());
        $trend = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = $now->copy()->subMonths($i);
            $sum = (float) $projects
                ->filter(fn ($p) => $p->start_date && $p->start_date->year === $m->year && $p->start_date->month === $m->month)
                ->sum('contract_value');
            $trend[] = ['label' => $m->translatedFormat('M Y'), 'value' => $sum];
        }
        $trendMax = collect($trend)->max('value') ?: 1;

        $stats = [
            'total' => $projects->count(),
            'active' => $activeProjects->count(),
            'completed' => $projects->where('status', 'completed')->count(),
            'on_hold' => $projects->where('status', 'on_hold')->count(),
            'total_contract_value' => (float) $projects->sum('contract_value'),
            'avg_progress' => $projects->count() ? (int) round($projects->avg('progress')) : 0,
        ];

        $byStatus = $projects->groupBy('status')->map->count()->all();

        // مشاريعي (المستخدم مدير أو مشرف)
        $user = $request->user();
        $userId = $user->id;
        $myProjects = $projects->filter(
            fn ($p) => $p->project_manager_id === $userId || $p->supervisor_id === $userId
        )->values();

        $recentProjects = $projects->sortByDesc('id')->take(6)->values();

        // تفصيل مالي لكل مشروع (للوحة المالية)
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

        return view('dashboard', [
            'homeView' => $user->homeView(),
            'stats' => $stats,
            'byStatus' => $byStatus,
            'recentProjects' => $recentProjects,
            'myProjects' => $myProjects,
            'myTasksCount' => \App\Models\Task::where('assigned_to', $userId)->whereNotIn('status', ['completed'])->count(),
            'projectsFinance' => $projectsFinance,
            'fin' => [
                'active_value' => $activeValue,
                'active_count' => $activeProjects->count(),
                'total_paid' => $totalActual,
                'commitments' => $commitments,
                'remaining_to_pay' => $remainingToPay,
                'payment_rate' => $paymentRate,
                'total_invoiced' => $totalCommitted,
            ],
            'health' => $health,
            'topClients' => $topClients,
            'topVendors' => $topVendors,
            'trend' => $trend,
            'trendMax' => $trendMax,
        ]);
    }
}
