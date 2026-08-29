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
        abort_unless(auth()->user()?->hasPermission('schedule.edit'), 403);

        $data = $this->validated($request);
        $data['is_critical'] = $request->boolean('is_critical');
        $data['order'] = (int) $project->activities()->max('order') + 10;

        $project->activities()->create($data);

        return back()->with('success', __('app.activity_created'));
    }

    public function update(Request $request, Activity $activity): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        abort_unless(auth()->user()?->hasPermission('schedule.edit'), 403);

        $data = $this->validated($request, true);
        if ($request->has('is_critical')) {
            $data['is_critical'] = $request->boolean('is_critical');
        }
        $activity->update($data);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'status' => $activity->status,
                'status_class' => $activity->statusClass(),
                'variance' => $activity->variance(),
                'is_critical' => $activity->is_critical,
            ]);
        }

        return back()->with('success', __('app.activity_updated'));
    }

    public function destroy(Activity $activity): RedirectResponse
    {
        abort_unless(auth()->user()?->hasPermission('schedule.edit'), 403);

        $activity->delete();

        return back()->with('success', __('app.activity_deleted'));
    }

    protected function validated(Request $request, bool $partial = false): array
    {
        $rule = fn (array $r) => $partial ? array_merge(['sometimes'], $r) : $r;
        $types = implode(',', Activity::REL_TYPES);

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
            'level' => $rule(['nullable', 'integer', 'min:0', 'max:5']),
            'pred1' => $rule(['nullable', 'string', 'max:20']),
            'type1' => $rule(['nullable', 'in:'.$types]),
            'lag1' => $rule(['nullable', 'integer', 'min:-999', 'max:999']),
            'pred2' => $rule(['nullable', 'string', 'max:20']),
            'type2' => $rule(['nullable', 'in:'.$types]),
            'lag2' => $rule(['nullable', 'integer', 'min:-999', 'max:999']),
            'pred3' => $rule(['nullable', 'string', 'max:20']),
            'type3' => $rule(['nullable', 'in:'.$types]),
            'lag3' => $rule(['nullable', 'integer', 'min:-999', 'max:999']),
        ]);
    }
}
