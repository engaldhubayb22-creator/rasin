<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Company;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::firstOrCreate(
            ['name' => 'شركة رسين للتطوير العقاري'],
            [
                'legal_name' => 'شركة رسين للتطوير العقاري',
                'currency' => 'SAR',
                'timezone' => 'Asia/Riyadh',
                'is_active' => true,
            ]
        );

        $admin = User::firstOrCreate(['email' => 'admin@rasin.sa'], [
            'company_id' => $company->id, 'name' => 'مدير النظام',
            'password' => Hash::make('password'), 'role' => 'admin',
            'job_title' => 'مدير النظام', 'is_active' => true,
        ]);

        $pm = User::firstOrCreate(['email' => 'pm@rasin.sa'], [
            'company_id' => $company->id, 'name' => 'عبدالله المدير',
            'password' => Hash::make('password'), 'role' => 'project_manager',
            'job_title' => 'مدير مشاريع', 'is_active' => true,
        ]);

        $eng = User::firstOrCreate(['email' => 'eng@rasin.sa'], [
            'company_id' => $company->id, 'name' => 'خالد المهندس',
            'password' => Hash::make('password'), 'role' => 'engineer',
            'job_title' => 'مهندس موقع', 'is_active' => true,
        ]);

        User::firstOrCreate(['email' => 'ceo@rasin.sa'], [
            'company_id' => $company->id, 'name' => 'فهد التنفيذي',
            'password' => Hash::make('password'), 'role' => 'executive',
            'job_title' => 'المدير التنفيذي', 'is_active' => true,
        ]);

        User::firstOrCreate(['email' => 'finance@rasin.sa'], [
            'company_id' => $company->id, 'name' => 'نورة المالية',
            'password' => Hash::make('password'), 'role' => 'finance',
            'job_title' => 'محاسب', 'is_active' => true,
        ]);

        $samples = [
            ['code' => 'PRJ-2026-001', 'name' => 'فيلا سكنية - حي الياسمين', 'client_name' => 'م. سعد الغامدي', 'location' => 'الرياض - الياسمين', 'contract_value' => 3200000, 'budget' => 2800000, 'progress' => 65, 'status' => 'active'],
            ['code' => 'PRJ-2026-002', 'name' => 'مجمع تجاري - طريق الملك فهد', 'client_name' => 'شركة الرواد', 'location' => 'الرياض - العليا', 'contract_value' => 18500000, 'budget' => 16000000, 'progress' => 30, 'status' => 'active'],
            ['code' => 'PRJ-2026-003', 'name' => 'مستودعات لوجستية', 'client_name' => 'مؤسسة النقل الحديث', 'location' => 'الخرج', 'contract_value' => 7400000, 'budget' => 6900000, 'progress' => 100, 'status' => 'completed'],
            ['code' => 'PRJ-2026-004', 'name' => 'تشطيب مكاتب إدارية', 'client_name' => 'بنك المستقبل', 'location' => 'جدة - الروضة', 'contract_value' => 2100000, 'budget' => 1950000, 'progress' => 10, 'status' => 'on_hold'],
            ['code' => 'PRJ-2026-005', 'name' => 'مدرسة أهلية - مبنى جديد', 'client_name' => 'مدارس النخبة', 'location' => 'الدمام', 'contract_value' => 9800000, 'budget' => 9000000, 'progress' => 48, 'status' => 'active'],
            ['code' => 'PRJ-2026-006', 'name' => 'ترميم مبنى تراثي', 'client_name' => 'أمانة المنطقة', 'location' => 'الدرعية', 'contract_value' => 4300000, 'budget' => 4000000, 'progress' => 0, 'status' => 'cancelled'],
        ];

        foreach ($samples as $i => $data) {
            $project = Project::firstOrCreate(['code' => $data['code']], array_merge($data, [
                'company_id' => $company->id,
                'project_manager_id' => $pm->id,
                'supervisor_id' => $eng->id,
                'start_date' => Carbon::now()->subMonths(6 - $i),
                'end_date' => Carbon::now()->addMonths(4 + $i),
                'description' => 'مشروع لعرض إمكانيات نظام متابعة المشاريع.',
            ]));

            if ($project->wasRecentlyCreated) {
                $this->seedWorkspace($project, $pm, $eng);
            }
        }
    }

    private function seedWorkspace(Project $project, User $pm, User $eng): void
    {
        // الفريق
        $project->members()->createMany([
            ['user_id' => $pm->id, 'team_role' => 'مدير المشروع', 'is_primary' => true],
            ['user_id' => $eng->id, 'team_role' => 'مهندس موقع', 'is_primary' => false],
        ]);

        // مهام
        $tasks = [
            ['title' => 'مراجعة المخططات المعمارية', 'assigned_to' => $eng->id, 'status' => 'completed', 'priority' => 'high', 'progress' => 100],
            ['title' => 'إعداد جدول الكميات', 'assigned_to' => $eng->id, 'status' => 'in_progress', 'priority' => 'normal', 'progress' => 40],
            ['title' => 'استخراج تصاريح البناء', 'assigned_to' => $pm->id, 'status' => 'pending', 'priority' => 'urgent', 'progress' => 0],
            ['title' => 'تعاقد مقاول الأعمال الكهربائية', 'assigned_to' => $pm->id, 'status' => 'blocked', 'priority' => 'high', 'progress' => 0],
        ];
        foreach ($tasks as $t) {
            $project->tasks()->create(array_merge($t, [
                'company_id' => $project->company_id,
                'created_by' => $pm->id,
                'due_date' => Carbon::now()->addWeeks(rand(1, 8)),
                'completed_at' => $t['status'] === 'completed' ? Carbon::now()->subDays(3) : null,
            ]));
        }

        // أنشطة الجدول الزمني (مخطط/فعلي/انحراف/حرج)
        $activities = [
            ['activity_code' => 'A10', 'phase' => '0. التجهيز', 'name' => 'تجهيز الموقع والأعمال الأولية', 'name_en' => 'Site establishment & temp works', 'duration_days' => 18, 'planned_percent' => 100, 'actual_percent' => 100, 'is_critical' => true, 'status' => 'completed'],
            ['activity_code' => 'A20', 'phase' => '1. الحفر', 'name' => 'الحفر والقواعد', 'name_en' => 'Excavation & foundations', 'duration_days' => 30, 'planned_percent' => 80, 'actual_percent' => 65, 'is_critical' => true, 'status' => 'in_progress'],
            ['activity_code' => 'A30', 'phase' => '2. الهيكل', 'name' => 'الأعمال الخرسانية', 'name_en' => 'Concrete structure', 'duration_days' => 90, 'planned_percent' => 30, 'actual_percent' => 30, 'is_critical' => false, 'status' => 'in_progress'],
            ['activity_code' => 'A40', 'phase' => '3. التشطيب', 'name' => 'أعمال التشطيبات', 'name_en' => 'Finishing works', 'duration_days' => 60, 'planned_percent' => 0, 'actual_percent' => 0, 'is_critical' => false, 'status' => 'not_started'],
        ];
        foreach ($activities as $i => $a) {
            $project->activities()->create(array_merge($a, [
                'order' => ($i + 1) * 10,
                'planned_start' => Carbon::now()->subMonths(3 - $i),
                'planned_finish' => Carbon::now()->addMonths($i + 1),
            ]));
        }

        // بنود الميزانية (المعتمد / المرتبط / المصروف) — كنِسب من ميزانية المشروع
        $budget = (float) $project->budget;
        $items = [
            ['item_code' => 'B10', 'category' => 'أعمال عامة', 'name' => 'التجهيز والأعمال المؤقتة', 'name_en' => 'Preliminaries & temp works', 'b' => 0.08, 'c' => 0.08, 'a' => 0.07],
            ['item_code' => 'B20', 'category' => 'أعمال ترابية', 'name' => 'الحفر والردم', 'name_en' => 'Earthworks', 'b' => 0.10, 'c' => 0.10, 'a' => 0.09],
            ['item_code' => 'B30', 'category' => 'الهيكل الإنشائي', 'name' => 'الأعمال الخرسانية والحديد', 'name_en' => 'Concrete & rebar', 'b' => 0.34, 'c' => 0.30, 'a' => 0.20],
            ['item_code' => 'B40', 'category' => 'التشطيبات', 'name' => 'أعمال التشطيبات الداخلية والخارجية', 'name_en' => 'Finishing works', 'b' => 0.24, 'c' => 0.12, 'a' => 0.05],
            ['item_code' => 'B50', 'category' => 'كهروميكانيك', 'name' => 'الأعمال الكهربائية والميكانيكية', 'name_en' => 'MEP works', 'b' => 0.18, 'c' => 0.10, 'a' => 0.04],
            ['item_code' => 'B60', 'category' => 'أعمال عامة', 'name' => 'إدارة المشروع والمصاريف الإدارية', 'name_en' => 'Management & overheads', 'b' => 0.06, 'c' => 0.06, 'a' => 0.03],
        ];
        foreach ($items as $i => $it) {
            $project->budgetItems()->create([
                'item_code' => $it['item_code'],
                'category' => $it['category'],
                'name' => $it['name'],
                'name_en' => $it['name_en'],
                'budgeted_amount' => round($budget * $it['b'], 2),
                'committed_amount' => round($budget * $it['c'], 2),
                'actual_amount' => round($budget * $it['a'], 2),
                'order' => ($i + 1) * 10,
            ]);
        }

        $this->seedSchedule($project, $pm);

        // متطلبات المشروع (بنود مرتبطة بمسؤولين)
        $reqs = [
            ['code' => $project->code.'-R01', 'title' => 'اعتماد مخطط مدخل العمارة', 'department' => 'projects_mgmt', 'assigned_to' => $eng->id, 'status' => 'urgent', 'due' => 4, 'note' => 'بانتظار اعتماد الاستشاري'],
            ['code' => $project->code.'-R02', 'title' => 'توريد وتركيب اللوحات الديكورية', 'department' => 'procurement', 'assigned_to' => $pm->id, 'status' => 'in_progress', 'due' => 10, 'note' => ''],
            ['code' => $project->code.'-R03', 'title' => 'متابعة طلب توصيل الكهرباء', 'department' => 'procurement', 'assigned_to' => $pm->id, 'status' => 'pending', 'due' => -3, 'note' => 'تم إصدار الاتفاقية'],
            ['code' => $project->code.'-R04', 'title' => 'تعميد مقاول النظافة', 'department' => 'projects_mgmt', 'assigned_to' => $eng->id, 'status' => 'completed', 'due' => -8, 'note' => ''],
            ['code' => $project->code.'-R05', 'title' => 'حل مشكلة صفاية البلكونة', 'department' => 'projects_mgmt', 'assigned_to' => $eng->id, 'status' => 'urgent', 'due' => -1, 'note' => 'جاري المعالجة'],
        ];
        foreach ($reqs as $i => $rq) {
            $project->requirements()->create([
                'code' => $rq['code'],
                'title' => $rq['title'],
                'note' => $rq['note'] ?: null,
                'department' => $rq['department'],
                'assigned_to' => $rq['assigned_to'],
                'status' => $rq['status'],
                'due_date' => Carbon::now()->addDays($rq['due']),
                'order' => ($i + 1) * 10,
            ]);
        }
    }

    /** نسخة جدول زمني مع أنشطة WBS (مراحل + أنشطة، حرجة/متأخرة) */
    private function seedSchedule(Project $project, User $pm): void
    {
        $version = $project->scheduleVersions()->create([
            'name' => 'نسخة شهر 4',
            'status' => 'pending',
            'period_start' => Carbon::now()->subMonths(3),
            'period_finish' => Carbon::now()->addMonths(9),
            'source_file' => 'schedule_v4.xlsx',
            'uploaded_by' => $pm->id,
            'notes' => 'تحديث الجدول الزمني — الشهر الرابع.',
        ]);

        // بنية WBS: مرحلة (level 1) ثم أنشطتها (level 2)
        $wbs = [
            ['1', 1, 'مرحلة أعمال خرسانة نظافة الدور الأرضي', 'Ground floor blinding concrete', 50, false, 0, 'in_progress', -30, 25],
            ['1.1', 2, 'صب نظافة الدور الأرضي الجزء الأول', 'Blinding pour — part 1', 100, false, 0, 'completed', -30, 25],
            ['1.2', 2, 'صب نظافة الدور الأرضي الجزء الثاني', 'Blinding pour — part 2', 0, false, 8, 'delayed', -29, 26],
            ['2', 1, 'أعمال خرسانة الدور الأرضي (الجزء الأول)', 'Ground floor concrete (part 1)', 0, false, 0, 'not_started', -24, 55],
            ['2.1', 2, 'حدادة أعمدة الدور الأرضي الجزء الأول', 'GF columns rebar — part 1', 0, true, 12, 'delayed', -28, 30],
            ['2.2', 2, 'نجارة أعمدة الدور الأرضي', 'GF columns formwork', 0, true, 11, 'delayed', -27, 33],
            ['2.3', 2, 'صب أعمدة الدور الأرضي', 'GF columns pour', 0, true, 10, 'delayed', -26, 36],
            ['2.4', 2, 'نجارة سقف الدور الأرضي', 'GF slab formwork', 0, true, 9, 'delayed', -20, 40],
            ['2.5', 2, 'حدادة سقف الدور الأرضي', 'GF slab rebar', 0, true, 8, 'delayed', -19, 43],
            ['2.6', 2, 'أعمال تأسيس السباكة والكهرباء والتكييف', 'MEP first fix', 0, true, 7, 'delayed', -18, 46],
            ['3', 1, 'مرحلة أعمال خرسانة الدور الأول (الجزء الأول)', 'First floor concrete (part 1)', 0, false, 0, 'not_started', 5, 90],
            ['3.1', 2, 'حدادة أعمدة الدور الأول', 'FF columns rebar', 0, true, 0, 'not_started', 5, 93],
            ['3.2', 2, 'صب أعمدة الدور الأول', 'FF columns pour', 0, false, 0, 'not_started', 10, 96],
        ];

        foreach ($wbs as $i => [$code, $level, $name, $nameEn, $percent, $critical, $delay, $status, $startOffset, $finishOffset]) {
            $version->activities()->create([
                'wbs' => $code,
                'level' => $level,
                'name' => $name,
                'name_en' => $nameEn,
                'planned_start' => Carbon::now()->addDays($startOffset),
                'planned_finish' => Carbon::now()->addDays($finishOffset),
                'actual_start' => $percent > 0 ? Carbon::now()->addDays($startOffset + 2) : null,
                'actual_finish' => $percent >= 100 ? Carbon::now()->addDays($finishOffset) : null,
                'percent' => $percent,
                'is_critical' => $critical,
                'delay_days' => $delay,
                'status' => $status,
                'order' => ($i + 1) * 10,
            ]);
        }
    }
}
