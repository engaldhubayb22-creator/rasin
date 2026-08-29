<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Project;
use Illuminate\View\View;

class ScheduleTrackerController extends Controller
{
    /** متتبّع الجدول الزمني بأسلوب بريمافيرا — شبكة كاملة قابلة للتعديل */
    public function index(Project $project): View
    {
        abort_unless(auth()->user()?->hasPermission('schedule.view'), 403);

        $activities = $project->activities()->orderBy('order')->orderBy('id')->get();

        // قائمة الأنشطة للربط (القوائم المنسدلة للأنشطة السابقة)
        $codes = $activities->filter(fn ($a) => filled($a->activity_code))
            ->map(fn ($a) => ['code' => $a->activity_code, 'name' => $a->displayName()])
            ->values();

        $leaves = $activities->filter(fn ($a) => ! $a->isPhaseRow());
        $totDur = max(1, (int) $leaves->sum('duration_days'));
        $kpis = [
            'count' => $leaves->count(),
            'planned' => (int) round($leaves->sum(fn ($a) => (int) $a->duration_days * (int) $a->planned_percent) / $totDur),
            'actual' => (int) round($leaves->sum(fn ($a) => (int) $a->duration_days * (int) $a->actual_percent) / $totDur),
            'critical' => $activities->where('is_critical', true)->count(),
            'completed' => $leaves->where('status', 'completed')->count(),
            'delayed' => $leaves->where('status', 'delayed')->count(),
        ];
        $kpis['variance'] = $kpis['actual'] - $kpis['planned'];

        return view('schedule.tracker', [
            'project' => $project,
            'grouped' => $activities->groupBy('phase'),
            'codes' => $codes,
            'statuses' => Activity::STATUSES,
            'relTypes' => Activity::REL_TYPES,
            'kpis' => $kpis,
            'canEdit' => auth()->user()->hasPermission('schedule.edit'),
        ]);
    }
}
