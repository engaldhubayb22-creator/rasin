<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\BudgetItemController;
use App\Http\Controllers\ChecklistItemController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\RequirementController;
use App\Http\Controllers\ScheduleVersionController;
use App\Http\Controllers\MyTasksController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectMemberController;
use App\Http\Controllers\TaskController;
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

    /*
    |----------------------------------------------------------------------
    | لاحقاً: المشتريات، المخططات، المتطلبات، الاعتمادات
    |----------------------------------------------------------------------
    */
});
