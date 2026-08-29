<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AdminChecklistTemplateController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\BudgetItemController;
use App\Http\Controllers\ChecklistItemController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DrawingController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\ProcurementController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\RequirementController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\ScheduleTrackerController;
use App\Http\Controllers\ScheduleVersionController;
use App\Http\Controllers\MyTasksController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectMemberController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

// ===== تبديل اللغة (متاح للجميع) =====
Route::get('/lang/{locale}', function (string $locale) {
    if (in_array($locale, SetLocale::SUPPORTED, true)) {
        session(['locale' => $locale]);
    }

    return back();
})->name('lang.switch');

// ===== المصادقة =====
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// ===== الصفحات المحمية =====
Route::middleware('auth')->group(function () {

    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // مهامي (عبر كل المشاريع)
    Route::get('/my-tasks', [MyTasksController::class, 'index'])->name('my-tasks');

    // المالية والتقارير (حسب الصلاحية)
    Route::get('/finance', [FinanceController::class, 'index'])->name('finance');
    Route::get('/reports', [ReportsController::class, 'index'])->name('reports');

    // مركز متابعة التشك لست (عبر كل المشاريع)
    Route::get('/checklist-center', [ChecklistItemController::class, 'center'])->name('checklist.center');

    // متابعة المتطلبات (مرتبطة بمهام الأعضاء)
    Route::get('/requirements', [RequirementController::class, 'index'])->name('requirements.index');
    Route::post('projects/{project}/requirements', [RequirementController::class, 'store'])->name('requirements.store');
    Route::patch('requirements/{requirement}', [RequirementController::class, 'update'])->name('requirements.update');
    Route::delete('requirements/{requirement}', [RequirementController::class, 'destroy'])->name('requirements.destroy');

    // المشاريع
    Route::resource('projects', ProjectController::class);

    // داخل المشروع: المهام / الجدول / الفريق
    Route::post('projects/{project}/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::patch('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

    // متتبّع الجدول الزمني (شبكة بريمافيرا القابلة للتعديل)
    Route::get('projects/{project}/schedule-tracker', [ScheduleTrackerController::class, 'index'])->name('schedule.tracker');
    Route::post('projects/{project}/activities', [ActivityController::class, 'store'])->name('activities.store');
    Route::patch('activities/{activity}', [ActivityController::class, 'update'])->name('activities.update');
    Route::delete('activities/{activity}', [ActivityController::class, 'destroy'])->name('activities.destroy');

    Route::post('projects/{project}/members', [ProjectMemberController::class, 'store'])->name('members.store');
    Route::delete('projects/{project}/members/{member}', [ProjectMemberController::class, 'destroy'])->name('members.destroy');

    // التشك لست (متطلبات المشروع الأساسية)
    Route::post('projects/{project}/checklist/generate', [ChecklistItemController::class, 'generate'])->name('checklist.generate');
    Route::post('projects/{project}/checklist', [ChecklistItemController::class, 'store'])->name('checklist.store');
    Route::patch('checklist/{checklistItem}', [ChecklistItemController::class, 'update'])->name('checklist.update');
    Route::delete('checklist/{checklistItem}', [ChecklistItemController::class, 'destroy'])->name('checklist.destroy');

    // الميزانية (بنود التكلفة)
    Route::post('projects/{project}/budget', [BudgetItemController::class, 'store'])->name('budget.store');
    Route::patch('budget/{budgetItem}', [BudgetItemController::class, 'update'])->name('budget.update');
    Route::delete('budget/{budgetItem}', [BudgetItemController::class, 'destroy'])->name('budget.destroy');

    // الجدول الزمني (نسخ + أنشطة WBS + اعتماد/رفض + Gantt)
    Route::post('projects/{project}/schedule', [ScheduleVersionController::class, 'store'])->name('schedule.store');
    Route::get('projects/{project}/schedule/{version}', [ScheduleVersionController::class, 'show'])->name('schedule.show');
    Route::get('projects/{project}/schedule/{version}/gantt', [ScheduleVersionController::class, 'gantt'])->name('schedule.gantt');
    Route::post('projects/{project}/schedule/{version}/decide', [ScheduleVersionController::class, 'decide'])->name('schedule.decide');
    Route::delete('projects/{project}/schedule/{version}', [ScheduleVersionController::class, 'destroy'])->name('schedule.destroy');

    // ===== الاعتمادات (مسار اعتماد متعدد الخطوات) =====
    Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
    Route::post('/approvals', [ApprovalController::class, 'store'])->name('approvals.store');
    Route::post('/approvals/{approval}/act', [ApprovalController::class, 'act'])->name('approvals.act');
    Route::delete('/approvals/{approval}', [ApprovalController::class, 'destroy'])->name('approvals.destroy');

    // ===== المخططات (سجل المخططات) =====
    Route::get('/drawings', [DrawingController::class, 'index'])->name('drawings.index');
    Route::post('/drawings', [DrawingController::class, 'store'])->name('drawings.store');
    Route::patch('/drawings/{drawing}', [DrawingController::class, 'update'])->name('drawings.update');
    Route::delete('/drawings/{drawing}', [DrawingController::class, 'destroy'])->name('drawings.destroy');

    // ===== مواعيد التوريد (مربوطة بالجدول الزمني) =====
    Route::get('/procurement', [ProcurementController::class, 'index'])->name('procurement.index');
    Route::post('/procurement', [ProcurementController::class, 'store'])->name('procurement.store');
    Route::patch('/procurement/{procurementItem}', [ProcurementController::class, 'update'])->name('procurement.update');
    Route::delete('/procurement/{procurementItem}', [ProcurementController::class, 'destroy'])->name('procurement.destroy');

    // ===== المستخدمون والأدوار والصلاحيات (users.manage) =====
    Route::get('/admin/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/admin/users', [UserController::class, 'store'])->name('users.store');
    Route::patch('/admin/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/admin/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::post('/admin/role-permissions/toggle', [RolePermissionController::class, 'toggle'])->name('role-permissions.toggle');

    // ===== الإدارة (المدير فقط) — قالب التشك لست الموحّد =====
    Route::get('/admin/checklist-template', [AdminChecklistTemplateController::class, 'index'])->name('admin.checklist-template.index');
    Route::post('/admin/checklist-template', [AdminChecklistTemplateController::class, 'store'])->name('admin.checklist-template.store');
    Route::patch('/admin/checklist-template/{templateItem}', [AdminChecklistTemplateController::class, 'update'])->name('admin.checklist-template.update');
    Route::delete('/admin/checklist-template/{templateItem}', [AdminChecklistTemplateController::class, 'destroy'])->name('admin.checklist-template.destroy');
    Route::post('/admin/checklist-template/reset', [AdminChecklistTemplateController::class, 'reset'])->name('admin.checklist-template.reset');

    /*
    |----------------------------------------------------------------------
    | لاحقاً: المشتريات، المخططات، المتطلبات، الاعتمادات
    |----------------------------------------------------------------------
    */
});
