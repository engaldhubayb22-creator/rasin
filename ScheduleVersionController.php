<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ScheduleVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduleVersionController extends Controller
{
    /** رفع نسخة جدول زمني جديدة */
    public function store(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'source_file' => ['nullable', 'file', 'mimes:mpp,xlsx,xls,pdf,csv', 'max:20480'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $version = $project->scheduleVersions()->create([
            'name' => $data['name'],
            'status' => 'pending',
            'notes' => $data['notes'] ?? null,
            'uploaded_by' => $request->user()->id,
            'source_file' => $request->hasFile('source_file')
                ? $request->file('source_file')->getClientOriginalName()
                : null,
        ]);

        return redirect()
            ->route('schedule.show', [$project, $version])
            ->with('success', __('app.sv_uploaded'));
    }

    /** صفحة نسخة الجدول (مؤشرات + تنبيهات + مراحل + أنشطة) */
    public function show(Project $project, ScheduleVersion $version): View
    {
        abort_unless($version->project_id === $project->id, 404);

        $version->load(['activities', 'uploader']);

        return view('schedule.show', [
            'project' => $project,
            'version' => $version,
        ]);
    }

    /** عرض Gantt */
    public function gantt(Project $project, ScheduleVersion $version): View
    {
        abort_unless($version->project_id === $project->id, 404);

        $version->load('activities');

        return view('schedule.gantt', [
            'project' => $project,
            'version' => $version,
        ]);
    }

    /** اعتماد / رفض النسخة */
    public function decide(Request $request, Project $project, ScheduleVersion $version): RedirectResponse
    {
        abort_unless($version->project_id === $project->id, 404);

        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
        ]);

        $version->update(['status' => $data['decision']]);

        return back()->with('success', $data['decision'] === 'approved'
            ? __('app.sv_approved_msg')
            : __('app.sv_rejected_msg'));
    }

    public function destroy(Project $project, ScheduleVersion $version): RedirectResponse
    {
        abort_unless($version->project_id === $project->id, 404);

        $version->delete();

        return redirect()
            ->route('projects.show', $project)
            ->with('success', __('app.sv_deleted'));
    }
}
