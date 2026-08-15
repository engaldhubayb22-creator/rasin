<?php

namespace App\Http\Controllers;

use App\Models\Requirement;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MyTasksController extends Controller
{
    public function index(Request $request): View
    {
        $userId = $request->user()->id;

        $tasks = Task::query()
            ->with('project')
            ->where('assigned_to', $userId)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderByRaw("CASE status WHEN 'in_progress' THEN 0 WHEN 'pending' THEN 1 WHEN 'blocked' THEN 2 ELSE 3 END")
            ->orderBy('due_date')
            ->get()
            ->groupBy('status');

        // المتطلبات المسندة للعضو (مرتبطة بمهامه)
        $myRequirements = Requirement::with('project')
            ->where('assigned_to', $userId)
            ->orderByRaw("CASE status WHEN 'urgent' THEN 0 WHEN 'in_progress' THEN 1 WHEN 'pending' THEN 2 ELSE 3 END")
            ->orderBy('due_date')
            ->get();

        return view('my-tasks', [
            'tasksByStatus' => $tasks,
            'taskStatuses' => Task::STATUSES,
            'myRequirements' => $myRequirements,
            'filters' => $request->only('status'),
        ]);
    }
}
