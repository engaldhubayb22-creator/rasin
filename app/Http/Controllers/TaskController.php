<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'priority' => ['required', 'in:'.implode(',', array_keys(Task::PRIORITIES))],
            'due_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
        ]);

        $project->tasks()->create([
            ...$data,
            'company_id' => $project->company_id,
            'status' => 'pending',
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', __('app.task_created'));
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['sometimes', 'in:'.implode(',', array_keys(Task::STATUSES))],
            'progress' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'title' => ['sometimes', 'string', 'max:255'],
            'assigned_to' => ['sometimes', 'nullable', 'exists:users,id'],
            'priority' => ['sometimes', 'in:'.implode(',', array_keys(Task::PRIORITIES))],
            'due_date' => ['sometimes', 'nullable', 'date'],
        ]);

        if (($data['status'] ?? null) === 'completed') {
            $data['completed_at'] = now();
            $data['progress'] = 100;
        } elseif (array_key_exists('status', $data)) {
            $data['completed_at'] = null;
        }

        $task->update($data);

        return back()->with('success', __('app.task_updated'));
    }

    public function destroy(Task $task): RedirectResponse
    {
        $task->delete();

        return back()->with('success', __('app.task_deleted'));
    }
}
