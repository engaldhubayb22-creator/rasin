<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        $data = $this->validated($request);
        $data['is_critical'] = $request->boolean('is_critical');
        $data['order'] = (int) $project->activities()->max('order') + 10;

        $project->activities()->create($data);

        return back()->with('success', __('app.activity_created'));
    }

    public function update(Request $request, Activity $activity): RedirectResponse
    {
        $data = $this->validated($request, true);
        $data['is_critical'] = $request->boolean('is_critical');
        $activity->update($data);

        return back()->with('success', __('app.activity_updated'));
    }

    public function destroy(Activity $activity): RedirectResponse
    {
        $activity->delete();

        return back()->with('success', __('app.activity_deleted'));
    }

    protected function validated(Request $request, bool $partial = false): array
    {
        $rule = fn (array $r) => $partial ? array_merge(['sometimes'], $r) : $r;

        return $request->validate([
            'activity_code' => $rule(['nullable', 'string', 'max:20']),
            'phase' => $rule(['nullable', 'string', 'max:120']),
            'name' => $rule(['required', 'string', 'max:255']),
            'name_en' => $rule(['nullable', 'string', 'max:255']),
            'duration_days' => $rule(['nullable', 'integer', 'min:0', 'max:65000']),
            'planned_start' => $rule(['nullable', 'date']),
            'planned_finish' => $rule(['nullable', 'date']),
            'planned_percent' => $rule(['nullable', 'integer', 'min:0', 'max:100']),
            'actual_percent' => $rule(['nullable', 'integer', 'min:0', 'max:100']),
            'is_critical' => $rule(['nullable', 'boolean']),
            'status' => $rule(['required', 'in:'.implode(',', array_keys(Activity::STATUSES))]),
        ]);
    }
}
