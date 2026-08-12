<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MyTasksController extends Controller
{
    public function index(Request $request): View
    {
        $tasks = Task::query()
            ->with('project')
            ->where('assigned_to', $request->user()->id)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderByRaw("CASE status WHEN 'in_progress' THEN 0 WHEN 'pending' THEN 1 WHEN 'blocked' THEN 2 ELSE 3 END")
            ->orderBy('due_date')
            ->get()
            ->groupBy('status');

        return view('my-tasks', [
            'tasksByStatus' => $tasks,
            'taskStatuses' => Task::STATUSES,
            'filters' => $request->only('status'),
        ]);
    }
}
