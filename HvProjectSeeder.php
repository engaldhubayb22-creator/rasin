<?php

namespace Database\Seeders;

use App\Models\Approval;
use App\Models\Company;
use App\Models\Drawing;
use App\Models\ProcurementItem;
use App\Models\Project;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * بيانات مشروع HV (فيلا حطين) الحقيقية — مستخرجة من ملف التحكم / النموذج.
 * آمن للتشغيل المتكرر (firstOrCreate). التشغيل:
 *   php artisan db:seed --class=Database\\Seeders\\HvProjectSeeder
 */
class HvProjectSeeder extends Seeder
{
    /** خرائط المراحل (رقم → اسم عربي) كما في النموذج */
    private array $phaseNames = [
        '0' => '0. التعبئة والترسية',
        '1' => '1. الحفر والأعمال التمكينية',
        '2' => '2. الأساسات',
        '3' => '3. الهيكل الخرساني',
        '4' => '4. الأغلفة والواجهات',
        '5' => '5. الميكانيكا والكهرباء',
        '6' => '6. التشطيبات الداخلية',
        '7' => '7. الموقع والتنسيق',
        '8' => '8. التسليم',
    ];

    /** خريطة أدوار النموذج (نص عربي) إلى مفاتيح أدوار النظام */
    private array $roleMap = [
        'مدير إدارة المشاريع' => 'admin',
        'الرئيس التنفيذي' => 'executive',
        'مدير مشروع' => 'project_manager',
        'مدير المشتريات' => 'project_manager',
        'مدير المالية' => 'finance',
        'موظف مشتريات' => 'engineer',
        'مهندس فني' => 'engineer',
        'موظف' => 'engineer',
    ];

    public function run(): void
    {
        $path = database_path('seed_data/hv_prototype.json');
        if (! is_file($path)) {
            $this->command?->warn("HV seed data not found at {$path}");

            return;
        }
        $D = json_decode(file_get_contents($path), true);

        $company = Company::firstOrCreate(
            ['name' => 'شركة رسين للتطوير العقاري'],
            ['legal_name' => 'شركة رسين للتطوير العقاري', 'currency' => 'SAR', 'timezone' => 'Asia/Riyadh', 'is_active' => true]
        );

        RolePermission::ensureSeeded();

        // ===== المستخدمون =====
        $usersByName = [];
        foreach ($D['users'] as $u) {
            $role = $this->roleMap[$u['role']] ?? 'engineer';
            $user = User::firstOrCreate(['email' => $u['email']], [
                'company_id' => $company->id,
                'name' => $u['name'],
                'password' => Hash::make('Rasine#2026'),
                'role' => $role,
                'job_title' => $u['role'],
                'is_active' => (bool) ($u['active'] ?? true),
            ]);
            $usersByName[$u['name']] = $user;
        }
        $defaultPm = collect($usersByName)->firstWhere('role', 'admin')
            ?? collect($usersByName)->first();

        // ===== المشاريع (الأربعة من النموذج) =====
        $projectsByName = [];
        foreach ($D['projects'] as $p) {
            $pmUser = $usersByName[$p['pm']] ?? $defaultPm;
            $project = Project::firstOrCreate(['code' => $p['code']], [
                'company_id' => $company->id,
                'name' => $p['name'],
                'type' => 'villa',
                'client_name' => $p['client'],
                'location' => $p['loc'],
                'budget' => $p['budget'],
                'contract_value' => $p['value'],
                'progress' => $p['progress'],
                'status' => $p['status'],
                'project_manager_id' => $pmUser?->id,
                'supervisor_id' => $defaultPm?->id,
                'start_date' => $p['start'],
                'end_date' => $p['finish'],
                'description' => 'مسطح البناء '.number_format($p['gfa']).' م² · مساحة الأرض '.number_format($p['site']).' م².',
            ]);
            $projectsByName[$p['name']] = $project;
        }

        // المشروع الأساسي HV
        $hv = $projectsByName[$D['projects'][0]['name']];

        // ===== الجدول الزمني (أنشطة المشروع) لـ HV =====
        if ($hv->activities()->count() === 0) {
            $s0 = Carbon::parse($D['sched']['start']);
            $todayIdx = 128; // 2026-12-15 نسبةً لبداية الجدول (كما في النموذج)
            foreach ($D['acts'] as $i => $a) {
                $dur = max(1, (int) $a['dur']);
                $plannedPct = (int) round(max(0, min(100, (($todayIdx - $a['s'] + 1) / $dur) * 100)));
                $actualPct = (int) $a['pct'];
                $status = $this->activityStatus($actualPct, $plannedPct, $todayIdx, $a['s']);
                $hv->activities()->create([
                    'activity_code' => $a['id'],
                    'phase' => $this->phaseNames[$a['p']] ?? $a['p'],
                    'name' => $a['ar'],
                    'name_en' => $a['en'],
                    'duration_days' => $dur,
                    'planned_start' => Carbon::parse($a['start']),
                    'planned_finish' => Carbon::parse($a['finish']),
                    'actual_start' => $actualPct > 0 ? Carbon::parse($a['start']) : null,
                    'actual_finish' => $actualPct >= 100 ? Carbon::parse($a['finish']) : null,
                    'planned_percent' => $plannedPct,
                    'actual_percent' => $actualPct,
                    'is_critical' => (bool) $a['crit'],
                    'status' => $status,
                    'order' => ($i + 1) * 10,
                ]);
            }
        }

        // ===== الاعتمادات =====
        $typeMap = [
            'Purchase Request' => 'purchase_request',
            'Purchase Order' => 'purchase_order',
            'Interim Payment Certificate' => 'payment_certificate',
            'Contractor Contract' => 'contract',
        ];
        foreach ($D['approvals'] as $ai => $a) {
            $project = $this->matchProject($projectsByName, $a['project']) ?? $hv;
            $exists = Approval::where('doc', $a['doc'])->exists();
            if ($exists) {
                continue;
            }
            $approval = Approval::create([
                'project_id' => $project->id,
                'doc' => $a['doc'],
                'type' => $typeMap[$a['type']] ?? 'other',
                'amount' => $a['amount'],
                'submitted_by' => $a['by'],
                'submitted_by_id' => $usersByName[$a['by']]->id ?? null,
                'submitted_at' => Carbon::parse($a['date']),
                'order' => ($ai + 1) * 10,
            ]);
            foreach ($a['steps'] as $si => $step) {
                [$roleLabel, $approverName, $status, $date] = $step + [null, null, 'pending', ''];
                $approval->steps()->create([
                    'role_label' => $roleLabel,
                    'approver_name' => ($approverName && $approverName !== '—') ? $approverName : null,
                    'approver_id' => $usersByName[$approverName]->id ?? null,
                    'status' => $status,
                    'decided_at' => $date ? Carbon::parse($date) : null,
                    'order' => ($si + 1) * 10,
                ]);
            }
        }

        // ===== المخططات (لـ HV) =====
        if (Drawing::where('project_id', $hv->id)->count() === 0) {
            $discMap = ['Architectural' => 'architectural', 'Structural' => 'structural', 'MEP' => 'mep'];
            foreach ($D['drawings'] as $di => $d) {
                $hv->drawings()->create([
                    'code' => $d['code'],
                    'title' => $d['title'],
                    'discipline' => $discMap[$d['disc']] ?? 'other',
                    'revision' => $d['rev'],
                    'drawing_date' => Carbon::parse($d['date']),
                    'status' => $d['status'],
                    'order' => ($di + 1) * 10,
                ]);
            }
        }

        // ===== مواعيد التوريد (لـ HV) =====
        if (ProcurementItem::where('project_id', $hv->id)->count() === 0) {
            foreach ($D['procurement'] as $pi => $p) {
                $hv->procurementItems()->create([
                    'item' => $p['item'],
                    'activity_code' => $p['act'],
                    'responsible' => $p['resp'],
                    'need_by' => Carbon::parse($p['need']),
                    'select_by' => Carbon::parse($p['selby']),
                    'order' => ($pi + 1) * 10,
                ]);
            }
        }

        $this->command?->info('HV project data seeded: '.$hv->name);
    }

    private function activityStatus(int $actual, int $planned, int $todayIdx, int $s): string
    {
        if ($actual >= 100) {
            return 'completed';
        }
        if ($todayIdx < $s) {
            return 'not_started';
        }
        if ($actual < $planned - 10) {
            return 'delayed';
        }

        return 'in_progress';
    }

    private function matchProject(array $projectsByName, string $needle): ?Project
    {
        foreach ($projectsByName as $name => $project) {
            if (str_contains($name, $needle) || str_contains($needle, explode(' —', $name)[0])) {
                return $project;
            }
        }

        return null;
    }
}
