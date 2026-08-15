<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    /** قائمة المشاريع مع بحث وفلترة */
    public function index(Request $request): View
    {
        $projects = Project::query()
            ->with('projectManager')
            ->search($request->input('q'))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('projects.index', [
            'projects' => $projects,
            'statuses' => Project::STATUSES,
            'filters' => $request->only(['q', 'status']),
        ]);
    }

    /** نموذج إضافة مشروع */
    public function create(): View
    {
        return view('projects.create', [
            'project' => new Project(['status' => 'active', 'progress' => 0]),
            'statuses' => Project::STATUSES,
            'managers' => $this->managers(),
        ]);
    }

    /** حفظ مشروع جديد */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['company_id'] = $request->user()->company_id;

        $project = Project::create($data);

        // توليد التشك لست تلقائياً من القالب الموحّد
        $project->generateChecklist();

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'تم إنشاء المشروع بنجاح.');
    }

    /** مساحة عمل المشروع (تبويبات) */
    public function show(Project $project): View
    {
        $project->load([
            'projectManager', 'supervisor', 'company',
            'tasks.assignee',
            'activities',
            'members.user',
            'budgetItems',
            'scheduleVersions.uploader',
            'scheduleVersions.activities',
            'checklistItems.assignee',
            'checklistItems.approver',
        ]);

        $taskStats = [
            'open' => $project->tasks->whereNotIn('status', ['completed'])->count(),
            'completed' => $project->tasks->where('status', 'completed')->count(),
            'total' => $project->tasks->count(),
        ];

        return view('projects.show', [
            'project' => $project,
            'statuses' => Project::STATUSES,
            'taskStatuses' => \App\Models\Task::STATUSES,
            'priorities' => \App\Models\Task::PRIORITIES,
            'activityStatuses' => \App\Models\Activity::STATUSES,
            'managers' => $this->managers(),
            'taskStats' => $taskStats,
        ]);
    }

    /** نموذج تعديل مشروع */
    public function edit(Project $project): View
    {
        return view('projects.edit', [
            'project' => $project,
            'statuses' => Project::STATUSES,
            'managers' => $this->managers(),
        ]);
    }

    /** تحديث مشروع */
    public function update(Request $request, Project $project): RedirectResponse
    {
        $project->update($this->validated($request));

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'تم تحديث بيانات المشروع.');
    }

    /** حذف مشروع (Soft Delete) */
    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        return redirect()
            ->route('projects.index')
            ->with('success', 'تم حذف المشروع.');
    }

    /** قواعد التحقق المشتركة */
    protected function validated(Request $request): array
    {
        return $request->validate([
            'code' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'in:'.implode(',', array_keys(Project::TYPES))],
            'description' => ['nullable', 'string'],
            'project_manager_id' => ['nullable', 'exists:users,id'],
            'supervisor_id' => ['nullable', 'exists:users,id'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'contract_value' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
            'status' => ['required', 'in:'.implode(',', array_keys(Project::STATUSES))],
        ], [], [
            'name' => 'اسم المشروع',
            'code' => 'كود المشروع',
            'client_name' => 'اسم العميل',
            'location' => 'الموقع',
            'budget' => 'الميزانية',
            'contract_value' => 'قيمة العقد',
            'start_date' => 'تاريخ البدء',
            'end_date' => 'تاريخ الانتهاء',
            'progress' => 'نسبة الإنجاز',
            'status' => 'الحالة',
        ]);
    }

    /** قائمة المستخدمين المرشحين كمدراء/مشرفين */
    protected function managers()
    {
        return User::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'job_title']);
    }
}
