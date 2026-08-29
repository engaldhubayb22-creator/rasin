<?php

namespace Database\Seeders;

use App\Models\Approval;
use App\Models\Company;
use App\Models\ProcurementItem;
use App\Models\Project;
use App\Models\Requirement;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * البيانات الحقيقية لمشروع فيلا حطين (HV) من ملف التحكم الرسمي (Excel).
 * يستبدل بيانات HV السابقة ببيانات الملف: الجدول الزمني، الموازنة،
 * التوريد الخارجي، ترسيات الحزم (كاعتمادات)، وبوابتي ما قبل البدء والإشغال (كمتطلبات).
 * آمن للتكرار — يمسح بيانات HV في الوحدات المعنيّة ثم يعيد تحميلها.
 * التشغيل:  php artisan db:seed --class=Database\\Seeders\\HvRealSeeder
 */
class HvRealSeeder extends Seeder
{
    public function run(): void
    {
        // البيانات مضمّنة في هذا الملف (نوداك) لتفادي الحاجة لمجلد بيانات منفصل عند الرفع.
        $D = json_decode($this->data(), true);

        $company = Company::firstOrCreate(
            ['name' => 'شركة رسين للتطوير العقاري'],
            ['legal_name' => 'شركة رسين للتطوير العقاري', 'currency' => 'SAR', 'timezone' => 'Asia/Riyadh', 'is_active' => true]
        );
        RolePermission::ensureSeeded();

        $admin = User::where('role', 'admin')->first()
            ?? User::firstOrCreate(['email' => 'admin@rasin.sa'], [
                'company_id' => $company->id, 'name' => 'مدير النظام',
                'password' => Hash::make('Rasine#2026'), 'role' => 'admin', 'is_active' => true,
            ]);

        // ===== المشروع =====
        $p = $D['project'];
        $project = Project::updateOrCreate(['code' => $p['code']], [
            'company_id' => $company->id,
            'name' => $p['name'],
            'type' => 'villa',
            'client_name' => $p['owner'],
            'location' => 'الرياض — حي حطين',
            'budget' => $p['budget'],
            'contract_value' => $p['contract'],
            'progress' => 1,
            'status' => 'active',
            'project_manager_id' => $admin->id,
            'supervisor_id' => $admin->id,
            'start_date' => $p['start'],
            'end_date' => $p['finish'],
            'description' => "المالك: {$p['owner']} · المطوّر: {$p['developer']} · إدارة المشروع: {$p['pm_firm']} · التصميم: {$p['design']} · "
                ."مسطح البناء ".number_format($p['gfa'])." م² · مساحة الأرض ".number_format($p['site'])." م² · المدة {$p['duration']} يوم.",
        ]);

        // ===== الجدول الزمني (أنشطة المشروع) =====
        $project->activities()->delete();
        $order = 0;
        foreach ($D['schedule'] as $a) {
            if (! $a['id'] || ! $a['phase'] || $a['phase'] === 'None') {
                continue;
            }
            $order += 10;
            $project->activities()->create([
                'activity_code' => $a['id'],
                'phase' => $a['phase'],
                'name' => $a['ar'] ?: $a['en'],
                'name_en' => $a['en'],
                'duration_days' => (int) ($a['dur'] ?? 0),
                'planned_start' => $a['start'] ? Carbon::parse($a['start']) : null,
                'planned_finish' => $a['finish'] ? Carbon::parse($a['finish']) : null,
                'planned_percent' => (int) round(($a['planned'] ?? 0) * (($a['planned'] ?? 0) <= 1 ? 100 : 1)),
                'actual_percent' => (int) round(($a['actual'] ?? 0) * (($a['actual'] ?? 0) <= 1 ? 100 : 1)),
                'is_critical' => (bool) $a['crit'],
                'status' => $this->schedStatus($a['status']),
                'level' => (int) ($a['level'] ?? 2),
                'pred1' => $a['p1'] ?: null, 'type1' => $a['t1'] ?: null, 'lag1' => $a['p1'] ? (int) ($a['l1'] ?? 0) : null,
                'pred2' => $a['p2'] ?: null, 'type2' => $a['t2'] ?: null, 'lag2' => $a['p2'] ? (int) ($a['l2'] ?? 0) : null,
                'pred3' => $a['p3'] ?: null, 'type3' => $a['t3'] ?: null, 'lag3' => $a['p3'] ? (int) ($a['l3'] ?? 0) : null,
                'order' => $order,
            ]);
        }

        // ===== الموازنة =====
        $project->budgetItems()->delete();
        $order = 0;
        foreach ($D['budget'] as $b) {
            $order += 10;
            $committed = str_contains((string) $b['name'], 'Earthworks') ? (float) $b['low'] : 0; // الحفر مُرسى
            $project->budgetItems()->create([
                'item_code' => 'B'.$order,
                'category' => $b['kind'] === 'indirect' ? 'تكاليف غير مباشرة' : 'حزم الأعمال',
                'name' => $b['name'],
                'budgeted_amount' => (float) ($b['low'] ?? 0),
                'committed_amount' => $committed,
                'actual_amount' => 0,
                'notes' => $b['window'] ?: null,
                'order' => $order,
            ]);
        }

        // ===== مواعيد التوريد الخارجي (China) =====
        ProcurementItem::where('project_id', $project->id)->delete();
        $order = 0;
        foreach ($D['procurement'] as $pr) {
            if (! $pr['need_by'] || ! $pr['select_by']) {
                continue;
            }
            $order += 10;
            $project->procurementItems()->create([
                'item' => $pr['ar'] ?: $pr['item'],
                'activity_code' => $pr['act'],
                'responsible' => $pr['resp'],
                'need_by' => Carbon::parse($pr['need_by']),
                'select_by' => Carbon::parse($pr['select_by']),
                'note' => 'دورة التوريد '.(int) $pr['cycle'].' يوم — '.$pr['item'],
                'order' => $order,
            ]);
        }

        // ===== ترسية الحزم → اعتمادات (عقود) =====
        Approval::where('project_id', $project->id)->delete();
        $order = 0;
        foreach ($D['awards'] as $aw) {
            $order += 10;
            $approval = $project->approvals()->create([
                'doc' => $aw['package'],
                'type' => 'contract',
                'amount' => $aw['value'] ? (float) $aw['value'] : null,
                'submitted_by' => $aw['awarded_to'] ?: '—',
                'submitted_at' => $aw['award_date'] ? Carbon::parse($aw['award_date']) : null,
                'note' => trim(($aw['scope'] ?? '').($aw['notes'] ? ' — '.$aw['notes'] : '')) ?: null,
                'order' => $order,
            ]);
            foreach ($this->awardSteps($aw['status']) as $si => [$label, $status]) {
                $approval->steps()->create([
                    'role_label' => $label,
                    'approver_name' => $status === 'approved' ? ($aw['awarded_to'] ?: '—') : null,
                    'status' => $status,
                    'decided_at' => $status === 'approved' && $aw['award_date'] ? Carbon::parse($aw['award_date']) : null,
                    'order' => ($si + 1) * 10,
                ]);
            }
        }

        // ===== بوابتا ما قبل البدء + الإشغال → متطلبات =====
        Requirement::where('project_id', $project->id)->delete();
        $order = 0;
        foreach ($D['prestart'] as $g) {
            $order += 10;
            $project->requirements()->create([
                'code' => 'PS-'.str_pad((string) $g['n'], 2, '0', STR_PAD_LEFT),
                'title' => ($g['ar'] && ! preg_match('/[A-Za-z]/', $g['ar'])) ? $g['ar'] : $g['principle'],
                'note' => trim('بوابة ما قبل البدء · '.($g['resp'] ? 'المسؤول: '.$g['resp'] : '').($g['status'] ? ' · '.$g['status'] : '')),
                'department' => str_contains((string) $g['phase'], 'First') ? 'executive' : 'projects_mgmt',
                'assigned_to' => null,
                'status' => $this->prestartStatus($g['status']),
                'due_date' => $g['due'] ? Carbon::parse($g['due']) : null,
                'order' => $order,
            ]);
        }
        foreach ($D['occupancy'] as $g) {
            $order += 10;
            $project->requirements()->create([
                'code' => 'OC-'.str_pad((string) $g['n'], 2, '0', STR_PAD_LEFT),
                'title' => $g['ar'] ?: $g['req'],
                'note' => trim('بوابة الإشغال · '.($g['actname'] ? 'النشاط: '.$g['actname'] : '').($g['assess'] ? ' · '.$g['assess'] : '')),
                'department' => 'projects_mgmt',
                'assigned_to' => null,
                'status' => ((int) $g['pct'] >= 100) ? 'completed' : 'pending',
                'due_date' => $g['due'] ? Carbon::parse($g['due']) : null,
                'order' => $order,
            ]);
        }

        $this->command?->info("HV real data seeded: {$project->name}"
            .' | activities='.$project->activities()->count()
            .' budget='.$project->budgetItems()->count()
            .' procurement='.$project->procurementItems()->count()
            .' approvals='.$project->approvals()->count()
            .' requirements='.$project->requirements()->count());
    }

    private function schedStatus($s): string
    {
        $s = mb_strtolower((string) $s);

        return match (true) {
            str_contains($s, 'complete') => 'completed',
            str_contains($s, 'progress') => 'in_progress',
            default => 'not_started',
        };
    }

    /** خطوات الاعتماد بحسب حالة الترسية */
    private function awardSteps(?string $status): array
    {
        return match ($status) {
            'Contract signed' => [['الترسية', 'approved'], ['توقيع العقد', 'approved']],
            'Awarded' => [['الترسية', 'approved'], ['توقيع العقد', 'pending']],
            'In preparation' => [['التجهيز للطرح', 'approved'], ['الترسية', 'pending']],
            default => [['الطرح', 'pending'], ['الترسية', 'pending']], // Not tendered
        };
    }

    private function prestartStatus(?string $s): string
    {
        $x = mb_strtolower((string) $s);

        return match (true) {
            str_contains($x, 'not available') => 'urgent',
            str_contains($x, 'not yet') || str_contains($x, 'not appointed') || str_contains($x, 'to be') => 'pending',
            str_contains($x, 'process') || str_contains($x, 'design') || str_contains($x, 'update') => 'in_progress',
            str_contains($x, 'complete') || str_contains($x, 'approved') => 'completed',
            default => 'pending',
        };
    }

    /** بيانات ملف التحكم الرسمي (Excel) مضمّنة كنص JSON */
    private function data(): string
    {
        return <<<'HVJSON'
{"project":{"name":"Hittin Villa (HV / HH) — Riyadh","code":"HV-001","owner":"عمار عبدالعزيز التويجري","developer":"رسين العقارية","pm_firm":"Gulf Link Project Engineering","design":"Atelier E Design Ltd. — Hong Kong","site":7792,"gfa":14673.71,"start":"2026-12-15","finish":"2028-03-31","duration":577,"budget":43305310,"contract":51805310},"schedule":[{"id":"P00","phase":"0. Pre-Start Requirements","en":"PRE-START GATE (Core Principles)","ar":"بوابة ما قبل البدء (Core Principles)","dur":105,"start":"2026-09-01","finish":"2026-12-14","crit":0,"planned":0,"actual":0,"status":"Not started","level":0,"p1":null,"t1":null,"l1":null,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"P10","phase":"0. Pre-Start Requirements","en":"Appoint Supervision Consultant","ar":"تعيين مكتب الإشراف","dur":25,"start":"2026-09-01","finish":"2026-09-26","crit":1,"planned":0,"actual":1,"status":"Complete","level":1,"p1":null,"t1":null,"l1":null,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"P20","phase":"0. Pre-Start Requirements","en":"Appoint Project Manager","ar":"تعيين مدير المشروع","dur":34,"start":"2026-09-27","finish":"2026-10-31","crit":0,"planned":0,"actual":0,"status":"Not started","level":1,"p1":"P10","t1":"FS","l1":0,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"P30","phase":"0. Pre-Start Requirements","en":"Structural design package (approved)","ar":"تسليم حزمة التصميم الإنشائي (معتمد)","dur":55,"start":"2026-09-01","finish":"2026-10-26","crit":1,"planned":0,"actual":0,"status":"Not started","level":1,"p1":null,"t1":null,"l1":null,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"P40","phase":"0. Pre-Start Requirements","en":"Architectural design package (approved)","ar":"تسليم حزمة التصميم المعماري (معتمد)","dur":55,"start":"2026-09-01","finish":"2026-10-26","crit":1,"planned":0,"actual":0,"status":"Not started","level":1,"p1":null,"t1":null,"l1":null,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"P50","phase":"0. Pre-Start Requirements","en":"MEP designer appointment & package","ar":"تعيين مصمم الكهروميكانيك وتسليم الحزمة","dur":29,"start":"2026-10-27","finish":"2026-11-25","crit":1,"planned":0,"actual":0,"status":"Not started","level":1,"p1":"P30","t1":"FS","l1":0,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"P60","phase":"0. Pre-Start Requirements","en":"Building permit — submission & approval","ar":"رخصة البناء — تقديم واعتماد","dur":70,"start":"2026-10-01","finish":"2026-12-10","crit":1,"planned":0,"actual":0,"status":"Not started","level":1,"p1":null,"t1":null,"l1":null,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"P70","phase":"0. Pre-Start Requirements","en":"Budget & schedule approval","ar":"اعتماد الموازنة والجدول الزمني","dur":19,"start":"2026-10-27","finish":"2026-11-15","crit":0,"planned":0,"actual":0,"status":"Not started","level":1,"p1":"P40","t1":"FS","l1":0,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"P80","phase":"0. Pre-Start Requirements","en":"Structure package tender & award","ar":"مناقصة وترسية حزمة الهيكل","dur":42,"start":"2026-10-27","finish":"2026-12-08","crit":1,"planned":0,"actual":0,"status":"Not started","level":1,"p1":"P30","t1":"FS","l1":0,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"P90","phase":"0. Pre-Start Requirements","en":"Vendor list & standard contracts","ar":"قائمة الموردين والعقود النموذجية","dur":60,"start":"2026-10-01","finish":"2026-11-30","crit":0,"planned":0,"actual":0,"status":"Not started","level":1,"p1":null,"t1":null,"l1":null,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"P95","phase":"0. Pre-Start Requirements","en":"Mobilization & site establishment","ar":"التعبئة وتجهيز الموقع","dur":13,"start":"2026-12-01","finish":"2026-12-14","crit":1,"planned":0,"actual":0,"status":"Not started","level":1,"p1":"P80","t1":"FS","l1":-8,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"C00","phase":"1. Earthworks & Concrete","en":"EARTHWORKS & CONCRETE","ar":"أعمال الحفر والخرسانات","dur":141,"start":"2026-12-15","finish":"2027-05-05","crit":0,"planned":0,"actual":0,"status":"Not started","level":0,"p1":null,"t1":null,"l1":null,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"C10","phase":"1. Earthworks & Concrete","en":"Excavation & earthworks","ar":"الحفر والترابيات","dur":31,"start":"2026-12-15","finish":"2027-01-15","crit":1,"planned":0,"actual":0,"status":"Not started","level":1,"p1":"P95","t1":"FS","l1":0,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"C20","phase":"1. Earthworks & Concrete","en":"Soil improvement & blinding","ar":"تحسين التربة والخرسانة العادية","dur":20,"start":"2027-01-05","finish":"2027-01-25","crit":1,"planned":0,"actual":0,"status":"Not started","level":1,"p1":"C10","t1":"SS","l1":21,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"C30","phase":"1. Earthworks & Concrete","en":"Raft foundation","ar":"اللبشة المسلحة","dur":21,"start":"2027-01-20","finish":"2027-02-10","crit":1,"planned":0,"actual":0,"status":"Not started","level":1,"p1":"C20","t1":"SS","l1":15,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"C40","phase":"1. Earthworks & Concrete","en":"Basement walls & columns","ar":"حوائط وأعمدة القبو","dur":27,"start":"2027-02-01","finish":"2027-02-28","crit":1,"planned":0,"actual":0,"status":"Not started","level":1,"p1":"C30","t1":"SS","l1":12,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"C50","phase":"1. Earthworks & Concrete","en":"Basement slab (post-tension)","ar":"بلاطة القبو (بوست تنشن)","dur":23,"start":"2027-02-20","finish":"2027-03-15","crit":1,"planned":0,"actual":0,"status":"Not started","level":1,"p1":"C40","t1":"SS","l1":19,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"C60","phase":"1. Earthworks & Concrete","en":"Ground & first floor frame","ar":"أعمدة وبلاطات الأرضي والأول","dur":26,"start":"2027-03-10","finish":"2027-04-05","crit":1,"planned":0,"actual":0,"status":"Not started","level":1,"p1":"C50","t1":"SS","l1":18,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"C70","phase":"1. Earthworks & Concrete","en":"Roof slab, stairs & cores","ar":"بلاطة السطح والأدراج والأنوية","dur":21,"start":"2027-03-25","finish":"2027-04-15","crit":1,"planned":0,"actual":0,"status":"Not started","level":1,"p1":"C60","t1":"SS","l1":15,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"C80","phase":"1. Earthworks & Concrete","en":"Waterproofing & backfilling","ar":"العزل المائي والردم","dur":34,"start":"2027-04-01","finish":"2027-05-05","crit":0,"planned":0,"actual":0,"status":"Not started","level":1,"p1":"C70","t1":"SS","l1":7,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"D00","phase":"2. Masonry & Finishes","en":"MASONRY & FINISHES","ar":"المباني والتشطيبات","dur":379,"start":"2027-02-15","finish":"2028-02-29","crit":0,"planned":0,"actual":0,"status":"Not started","level":0,"p1":null,"t1":null,"l1":null,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"D10","phase":"2. Masonry & Finishes","en":"Blockwork & masonry","ar":"المباني والبلوك","dur":166,"start":"2027-02-15","finish":"2027-07-31","crit":1,"planned":0,"actual":0,"status":"Not started","level":1,"p1":"C40","t1":"SS","l1":14,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"D20","phase":"2. Masonry & Finishes","en":"MEP first fix","ar":"تمديدات الكهروميكانيك الأولية","dur":199,"start":"2027-03-15","finish":"2027-09-30","crit":1,"planned":0,"actual":0,"status":"Not started","level":1,"p1":"D10","t1":"SS","l1":28,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"D30","phase":"2. Masonry & Finishes","en":"Plaster & screed","ar":"اللياسة والصبات","dur":153,"start":"2027-04-15","finish":"2027-09-15","crit":1,"planned":0,"actual":0,"status":"Not started","level":1,"p1":"D10","t1":"SS","l1":59,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"D40","phase":"2. Masonry & Finishes","en":"Roof & wet-area waterproofing","ar":"عزل الأسطح والحمامات","dur":121,"start":"2027-06-01","finish":"2027-09-30","crit":0,"planned":0,"actual":0,"status":"Not started","level":1,"p1":"D30","t1":"SS","l1":47,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"D50","phase":"2. Masonry & Finishes","en":"External facades (stone)","ar":"الواجهات الخارجية (الحجر)","dur":167,"start":"2027-07-01","finish":"2027-12-15","crit":0,"planned":0,"actual":0,"status":"Not started","level":1,"p1":"D10","t1":"SS","l1":136,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"D60","phase":"2. Masonry & Finishes","en":"Aluminium & glazing","ar":"الألمنيوم والزجاج","dur":136,"start":"2027-08-01","finish":"2027-12-15","crit":0,"planned":0,"actual":0,"status":"Not started","level":1,"p1":"D50","t1":"SS","l1":31,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"D70","phase":"2. Masonry & Finishes","en":"Gypsum & decorative ceilings","ar":"الأسقف الجبسية والديكورية","dur":136,"start":"2027-09-01","finish":"2028-01-15","crit":0,"planned":0,"actual":0,"status":"Not started","level":1,"p1":"D30","t1":"SS","l1":139,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"D80","phase":"2. Masonry & Finishes","en":"Floor & bathroom tiling","ar":"بلاط البيت والحمامات","dur":137,"start":"2027-10-01","finish":"2028-02-15","crit":1,"planned":0,"actual":0,"status":"Not started","level":1,"p1":"D30","t1":"FS","l1":15,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"D90","phase":"2. Masonry & Finishes","en":"Internal & external doors","ar":"الأبواب الداخلية والخارجية","dur":77,"start":"2027-11-15","finish":"2028-01-31","crit":0,"planned":0,"actual":0,"status":"Not started","level":1,"p1":"D80","t1":"SS","l1":45,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"D95","phase":"2. Masonry & Finishes","en":"Internal & external painting","ar":"الدهانات الداخلية والخارجية","dur":90,"start":"2027-12-01","finish":"2028-02-29","crit":1,"planned":0,"actual":0,"status":"Not started","level":1,"p1":"D80","t1":"SS","l1":61,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"E00","phase":"3. Systems & Equipment","en":"SYSTEMS & EQUIPMENT","ar":"الأنظمة والتجهيزات","dur":198,"start":"2027-08-01","finish":"2028-02-15","crit":0,"planned":0,"actual":0,"status":"Not started","level":0,"p1":null,"t1":null,"l1":null,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"E10","phase":"3. Systems & Equipment","en":"HVAC & ventilation","ar":"التكييف والتهوية","dur":121,"start":"2027-09-01","finish":"2027-12-31","crit":0,"planned":0,"actual":0,"status":"Not started","level":1,"p1":"D20","t1":"SS","l1":170,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"E20","phase":"3. Systems & Equipment","en":"Plumbing & drainage","ar":"أعمال السباكة والصرف","dur":121,"start":"2027-08-01","finish":"2027-11-30","crit":0,"planned":0,"actual":0,"status":"Not started","level":1,"p1":"D20","t1":"SS","l1":139,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"E30","phase":"3. Systems & Equipment","en":"Electrical & lighting","ar":"الكهرباء والإنارة","dur":138,"start":"2027-09-15","finish":"2028-01-31","crit":1,"planned":0,"actual":0,"status":"Not started","level":1,"p1":"D20","t1":"SS","l1":184,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"E40","phase":"3. Systems & Equipment","en":"Sanitary fixtures","ar":"الأدوات الصحية","dur":76,"start":"2027-12-01","finish":"2028-02-15","crit":0,"planned":0,"actual":0,"status":"Not started","level":1,"p1":"D80","t1":"SS","l1":61,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"E50","phase":"3. Systems & Equipment","en":"Fire & life-safety systems","ar":"أنظمة الحريق والسلامة","dur":122,"start":"2027-10-01","finish":"2028-01-31","crit":1,"planned":0,"actual":0,"status":"Not started","level":1,"p1":"D20","t1":"SS","l1":200,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"E60","phase":"3. Systems & Equipment","en":"Elevators","ar":"المصاعد","dur":106,"start":"2027-10-01","finish":"2028-01-15","crit":1,"planned":0,"actual":0,"status":"Not started","level":1,"p1":"D20","t1":"SS","l1":200,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"E70","phase":"3. Systems & Equipment","en":"Water tanks & pumps","ar":"الخزانات والمضخات","dur":90,"start":"2027-09-01","finish":"2027-11-30","crit":0,"planned":0,"actual":0,"status":"Not started","level":1,"p1":"D20","t1":"SS","l1":170,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"F00","phase":"4. External Works","en":"EXTERNAL WORKS","ar":"الأعمال الخارجية","dur":106,"start":"2027-11-15","finish":"2028-02-29","crit":0,"planned":0,"actual":0,"status":"Not started","level":0,"p1":null,"t1":null,"l1":null,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"F10","phase":"4. External Works","en":"Stair & balcony balustrades","ar":"درابزين السلالم والشرفات","dur":61,"start":"2027-12-01","finish":"2028-01-31","crit":0,"planned":0,"actual":0,"status":"Not started","level":1,"p1":"D80","t1":"SS","l1":61,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"F20","phase":"4. External Works","en":"Entrances & ramps","ar":"المداخل والرامبات","dur":61,"start":"2027-11-15","finish":"2028-01-15","crit":0,"planned":0,"actual":0,"status":"Not started","level":1,"p1":"D80","t1":"SS","l1":45,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"F30","phase":"4. External Works","en":"Car parking","ar":"مواقف السيارات","dur":61,"start":"2027-12-01","finish":"2028-01-31","crit":0,"planned":0,"actual":0,"status":"Not started","level":1,"p1":"D80","t1":"SS","l1":61,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"F40","phase":"4. External Works","en":"Boundary wall & gates","ar":"السور والبوابات","dur":76,"start":"2027-12-01","finish":"2028-02-15","crit":0,"planned":0,"actual":0,"status":"Not started","level":1,"p1":"D80","t1":"SS","l1":61,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"F50","phase":"4. External Works","en":"Pavements & storm drainage","ar":"الأرصفة وتصريف مياه الأمطار","dur":76,"start":"2027-12-15","finish":"2028-02-29","crit":0,"planned":0,"actual":0,"status":"Not started","level":1,"p1":"D80","t1":"SS","l1":75,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"F60","phase":"4. External Works","en":"Landscaping & planting","ar":"التنسيق الخارجي والزراعة","dur":59,"start":"2028-01-01","finish":"2028-02-29","crit":0,"planned":0,"actual":0,"status":"Not started","level":1,"p1":"D80","t1":"SS","l1":92,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"G00","phase":"5. Close-Out & Occupancy","en":"CLOSE-OUT & OCCUPANCY","ar":"الإغلاق وشهادة الإشغال","dur":60,"start":"2028-02-01","finish":"2028-03-31","crit":0,"planned":0,"actual":0,"status":"Not started","level":0,"p1":null,"t1":null,"l1":null,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"G10","phase":"5. Close-Out & Occupancy","en":"Integrated testing & commissioning","ar":"الاختبار والتشغيل المتكامل","dur":28,"start":"2028-02-01","finish":"2028-02-29","crit":1,"planned":0,"actual":0,"status":"Not started","level":1,"p1":"E30","t1":"FS","l1":0,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"G20","phase":"5. Close-Out & Occupancy","en":"Debris removal & site cleaning","ar":"إزالة مخلفات البناء وتنظيف الموقع","dur":19,"start":"2028-02-15","finish":"2028-03-05","crit":0,"planned":0,"actual":0,"status":"Not started","level":1,"p1":"G10","t1":"SS","l1":14,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"G30","phase":"5. Close-Out & Occupancy","en":"Snagging & de-snagging","ar":"إغلاق الملاحظات (Snagging)","dur":24,"start":"2028-02-20","finish":"2028-03-15","crit":1,"planned":0,"actual":0,"status":"Not started","level":1,"p1":"G10","t1":"SS","l1":19,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"G40","phase":"5. Close-Out & Occupancy","en":"Compliance with permit & drawings","ar":"مطابقة المبنى للرخصة والمخططات","dur":14,"start":"2028-02-20","finish":"2028-03-05","crit":1,"planned":0,"actual":0,"status":"Not started","level":1,"p1":"G10","t1":"SS","l1":19,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"G50","phase":"5. Close-Out & Occupancy","en":"OC application & authority inspection","ar":"تقديم طلب شهادة الإشغال والتفتيش","dur":15,"start":"2028-03-10","finish":"2028-03-25","crit":1,"planned":0,"actual":0,"status":"Not started","level":1,"p1":"G40","t1":"FS","l1":4,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"G60","phase":"5. Close-Out & Occupancy","en":"OC issued & final handover","ar":"استلام شهادة الإشغال والتسليم النهائي","dur":5,"start":"2028-03-26","finish":"2028-03-31","crit":1,"planned":0,"actual":0,"status":"Not started","level":1,"p1":"G50","t1":"FS","l1":0,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"•  How to link one item to another:  type the predecessor's activity ID in the « Pred 1 » column (drop-down), pick the relationship type, then enter the lag in days (negative values are allowed = overlap).","phase":null,"en":null,"ar":null,"dur":null,"start":null,"finish":null,"crit":0,"planned":0,"actual":0,"status":null,"level":null,"p1":null,"t1":null,"l1":null,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"•  Relationship types:  FS = starts the day after the predecessor finishes, plus lag  ·  SS = starts with the predecessor's start, plus lag  ·  FF = finishes with the predecessor's finish, plus lag.","phase":null,"en":null,"ar":null,"dur":null,"start":null,"finish":null,"crit":0,"planned":0,"actual":0,"status":null,"level":null,"p1":null,"t1":null,"l1":null,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"•  One rule only:  the predecessor must sit in a row ABOVE its successor — otherwise a warning appears in the last column. You can attach up to three predecessors to one activity; the activity takes the latest of the driving dates.","phase":null,"en":null,"ar":null,"dur":null,"start":null,"finish":null,"crit":0,"planned":0,"actual":0,"status":null,"level":null,"p1":null,"t1":null,"l1":null,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"•  « Constraint start » = a start-no-earlier-than date (used for activities that have already started in reality). « Span » = the activity length in calendar days — change it and every successor shifts.","phase":null,"en":null,"ar":null,"dur":null,"start":null,"finish":null,"crit":0,"planned":0,"actual":0,"status":null,"level":null,"p1":null,"t1":null,"l1":null,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"•  The « Shift » column compares the calculated date against the Primavera baseline: zero = an exact match, positive = later than baseline.","phase":null,"en":null,"ar":null,"dur":null,"start":null,"finish":null,"crit":0,"planned":0,"actual":0,"status":null,"level":null,"p1":null,"t1":null,"l1":null,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null},{"id":"•  ★ = critical-path activity.  ·  The yellow « Actual % » column is the progress input.  ·  Blue rows are phase summaries rolled up automatically from their children — do not link them or enter progress on them.","phase":null,"en":null,"ar":null,"dur":null,"start":null,"finish":null,"crit":0,"planned":0,"actual":0,"status":null,"level":null,"p1":null,"t1":null,"l1":null,"p2":null,"t2":null,"l2":null,"p3":null,"t3":null,"l3":null}],"budget":[{"name":"Earthworks & backfill","share":0.0298491801582762,"low":1292628,"high":1292628,"window":"Oct-26 → Jan-27  (contract actually awarded — 64,100 m³)","kind":"direct"},{"name":"Concrete & structural frame","share":0.33,"low":13864185.06,"high":18981633.66,"window":"Nov-26 → Aug-27","kind":"direct"},{"name":"Masonry, plaster & waterproofing","share":0.1,"low":4201268.2,"high":5752010.2,"window":"Mar-27 → Sep-27","kind":"direct"},{"name":"Internal finishes","share":0.27,"low":11343424.14,"high":15530427.54,"window":"May-27 → Dec-27","kind":"direct"},{"name":"Facade, aluminium & glazing","share":0.09,"low":3781141.38,"high":5176809.18,"window":"Aug-27 → Nov-27","kind":"direct"},{"name":"MEP works","share":0.16,"low":6722029.12,"high":9203216.32,"window":"Jun-27 → Jan-28","kind":"direct"},{"name":"External works & landscape","share":0.05,"low":2100634.1,"high":2876005.1,"window":"Jul-27 → Dec-27","kind":"direct"},{"name":"رسوم التصميم","share":null,"low":1250000,"high":1250000,"window":null,"kind":"indirect"},{"name":"رسوم الإشراف","share":null,"low":2250000,"high":2250000,"window":null,"kind":"indirect"},{"name":"الأثاث والتجهيزات FF&E","share":null,"low":5000000,"high":5000000,"window":null,"kind":"indirect"}],"procurement":[{"item":"Travertine facade stone","ar":"حجر الترافرتين للواجهات","resp":"Facade Eng.","cycle":107,"act":"D50","need_by":"2027-06-24","select_by":"2027-03-09"},{"item":"Stone & marble flooring — FOH","ar":"رخام وحجر الأرضيات — المناطق الرئيسية","resp":"ID / QS","cycle":102,"act":"D80","need_by":"2027-09-24","select_by":"2027-06-14"},{"item":"Stone countertops","ar":"أسطح حجرية (كاونترتوب)","resp":"Interior Design","cycle":87,"act":"E40","need_by":"2027-11-24","select_by":"2027-08-29"},{"item":"Bespoke joinery & millwork","ar":"النجارة والأعمال الخشبية المصنّعة","resp":"ID / Joinery","cycle":140,"act":"D90","need_by":"2027-11-08","select_by":"2027-06-21"},{"item":"Wardrobes & casework","ar":"الخزائن وأعمال الدواليب","resp":"Interior Design","cycle":107,"act":"D90","need_by":"2027-11-08","select_by":"2027-07-24"},{"item":"Internal wood doors (custom)","ar":"الأبواب الخشبية الداخلية (مفصّلة)","resp":"Interior Design","cycle":107,"act":"D90","need_by":"2027-11-08","select_by":"2027-07-24"},{"item":"External wood & pivot doors","ar":"الأبواب الخارجية والمحورية","resp":"Architect","cycle":107,"act":"D90","need_by":"2027-11-08","select_by":"2027-07-24"},{"item":"Passenger elevators","ar":"المصاعد","resp":"MEP","cycle":120,"act":"E60","need_by":"2027-09-24","select_by":"2027-05-27"},{"item":"Car turntable (showroom)","ar":"منصة دوران السيارات","resp":"MEP","cycle":140,"act":"F30","need_by":"2027-11-24","select_by":"2027-07-07"},{"item":"Aluminium & glazing system","ar":"نظام الألمنيوم والزجاج","resp":"Facade Eng.","cycle":112,"act":"D60","need_by":"2027-07-25","select_by":"2027-04-04"},{"item":"Motorised shutters & drapery","ar":"الستائر الآلية والمعالجات","resp":"ID / AV","cycle":92,"act":"D95","need_by":"2027-11-24","select_by":"2027-08-24"},{"item":"Interior feature lighting","ar":"الإنارة الداخلية المميزة","resp":"ID / Lighting","cycle":90,"act":"E30","need_by":"2027-09-08","select_by":"2027-06-10"},{"item":"Bespoke chandeliers","ar":"الثريات المصنّعة خصيصًا","resp":"ID / Lighting","cycle":107,"act":"E30","need_by":"2027-09-08","select_by":"2027-05-24"},{"item":"Exterior & landscape lighting","ar":"الإنارة الخارجية وإنارة التنسيق","resp":"Landscape","cycle":85,"act":"F60","need_by":"2027-12-25","select_by":"2027-10-01"},{"item":"Wiring devices & switches","ar":"المفاتيح والأفياش","resp":"MEP","cycle":75,"act":"E30","need_by":"2027-09-08","select_by":"2027-06-25"},{"item":"Sanitaryware — basins, WCs, tubs","ar":"الأدوات الصحية — مغاسل ومراحيض وأحواض","resp":"MEP / ID","cycle":102,"act":"E40","need_by":"2027-11-24","select_by":"2027-08-14"},{"item":"Shower trays & mixers","ar":"صواني ومخلطات الدش","resp":"MEP / ID","cycle":92,"act":"E40","need_by":"2027-11-24","select_by":"2027-08-24"},{"item":"Kitchen sinks & appliances","ar":"أحواض وأجهزة المطبخ","resp":"Interior Design","cycle":92,"act":"E40","need_by":"2027-11-24","select_by":"2027-08-24"},{"item":"LED mirrors","ar":"المرايا المضيئة LED","resp":"Interior Design","cycle":80,"act":"E40","need_by":"2027-11-24","select_by":"2027-09-05"},{"item":"Suspended timber ceilings","ar":"الأسقف الخشبية المعلّقة","resp":"Interior Design","cycle":102,"act":"D70","need_by":"2027-08-25","select_by":"2027-05-15"},{"item":"Wallpaper & wall coverings","ar":"ورق وأغطية الجدران","resp":"Interior Design","cycle":75,"act":"D95","need_by":"2027-11-24","select_by":"2027-09-10"},{"item":"Carpets","ar":"السجاد","resp":"ID / FF&E","cycle":85,"act":"D95","need_by":"2027-11-24","select_by":"2027-08-31"},{"item":"Porcelain & ceramic tiling","ar":"البورسلان والسيراميك","resp":"Interior Design","cycle":82,"act":"D80","need_by":"2027-09-24","select_by":"2027-07-04"},{"item":"Mosaic tiles (pools & spa)","ar":"الفسيفساء (المسابح والسبا)","resp":"Interior Design","cycle":92,"act":"D80","need_by":"2027-09-24","select_by":"2027-06-24"},{"item":"Sauna & steam cabins","ar":"كبائن الساونا والبخار","resp":"MEP / ID","cycle":107,"act":"E40","need_by":"2027-11-24","select_by":"2027-08-09"},{"item":"Whirlpool / freestanding tubs","ar":"أحواض الاستحمام الحرة والجاكوزي","resp":"Interior Design","cycle":107,"act":"E40","need_by":"2027-11-24","select_by":"2027-08-09"},{"item":"Basalt & stone pavers","ar":"بلاط البازلت والحجر الخارجي","resp":"Landscape","cycle":92,"act":"F50","need_by":"2027-12-08","select_by":"2027-09-07"},{"item":"Planters & site furnishings","ar":"أحواض الزراعة وأثاث الموقع","resp":"Landscape","cycle":80,"act":"F60","need_by":"2027-12-25","select_by":"2027-10-06"},{"item":"Water pumps & pool plant","ar":"مضخات ومحطات المسابح","resp":"MEP","cycle":87,"act":"E70","need_by":"2027-08-25","select_by":"2027-05-30"},{"item":"Timber cladding & paneling","ar":"التكسيات والألواح الخشبية","resp":"Interior Design","cycle":107,"act":"D70","need_by":"2027-08-25","select_by":"2027-05-10"},{"item":"Wood floor decking","ar":"الأرضيات الخشبية","resp":"Interior Design","cycle":102,"act":"D80","need_by":"2027-09-24","select_by":"2027-06-14"},{"item":"AV, automation & smart home","ar":"الأنظمة الذكية والصوتيات والأتمتة","resp":"AV / MEP","cycle":95,"act":"E30","need_by":"2027-09-08","select_by":"2027-06-05"},{"item":"Bespoke designed furniture (FF&E)","ar":"الأثاث المصمم خصيصًا (FF&E)","resp":"FF&E","cycle":140,"act":"G30","need_by":"2028-02-13","select_by":"2027-09-26"},{"item":"Fire-rated & service doors","ar":"أبواب الحريق وأبواب الخدمة","resp":"MEP","cycle":87,"act":"E50","need_by":"2027-09-24","select_by":"2027-06-29"},{"item":"Generators & transformers","ar":"المولدات والمحولات","resp":"MEP","cycle":110,"act":"E30","need_by":"2027-09-08","select_by":"2027-05-21"},{"item":"Bidets","ar":"البيديه","resp":"MEP / ID","cycle":92,"act":"E40","need_by":"2027-11-24","select_by":"2027-08-24"},{"item":"Shower fixtures / faucets","ar":"خلاطات ومخارج الدش","resp":"MEP / ID","cycle":92,"act":"E40","need_by":"2027-11-24","select_by":"2027-08-24"},{"item":"Motorized gates to B2","ar":"البوابات الآلية للقبو B2","resp":"MEP","cycle":97,"act":"F40","need_by":"2027-11-24","select_by":"2027-08-19"}],"awards":[{"package":"Lead Architect & Interior Design","scope":"Architecture + interiors, from 50% DD onward","type":"Consultant","awarded_to":"Atelier E Design Ltd. (HK)","award_date":null,"value":1250000,"status":"Contract signed","notes":null},{"package":"Supervision Consultant","scope":"Site supervision & QA/QC","type":"Consultant","awarded_to":null,"award_date":null,"value":null,"status":"Not tendered","notes":null},{"package":"Structural Engineer","scope":"RC + post-tension design","type":"Consultant","awarded_to":"Arabia for Design and Engineering Consulting","award_date":null,"value":55000,"status":"Awarded","notes":null},{"package":"MEP Engineer","scope":"Mechanical / electrical / plumbing","type":"Consultant","awarded_to":null,"award_date":null,"value":null,"status":"In preparation","notes":null},{"package":"Landscape Architect","scope":"Hardscape & softscape design","type":"Consultant","awarded_to":null,"award_date":null,"value":null,"status":"Not tendered","notes":null},{"package":"Geotechnical Investigation","scope":"Soil investigation & report","type":"Consultant","awarded_to":"FEG","award_date":"2026-07-26","value":null,"status":"Contract signed","notes":"Report complete"},{"package":"Excavation & Earthworks","scope":"Two-stage excavation, 64,100 m3","type":"Contractor","awarded_to":"AWARDED - 120 days","award_date":null,"value":1292628,"status":"Contract signed","notes":null},{"package":"Main Structure (RC + PT)","scope":"Raft, walls, columns, PT slabs, cores","type":"Contractor","awarded_to":null,"award_date":null,"value":null,"status":"In preparation","notes":"CRITICAL - must be awarded before Sub-Structure starts 29-Oct-26"},{"package":"Waterproofing","scope":"Tanking, wet areas, roof","type":"Contractor","awarded_to":null,"award_date":null,"value":null,"status":"Not tendered","notes":null},{"package":"Masonry & Plaster","scope":"Blockwork + internal plaster + screed","type":"Contractor","awarded_to":null,"award_date":null,"value":null,"status":"Not tendered","notes":null},{"package":"External Stone & Tiling","scope":"Supply & fix external stone","type":"Contractor","awarded_to":null,"award_date":null,"value":null,"status":"Not tendered","notes":null},{"package":"Aluminium & Glazing","scope":"Windows, curtain wall, external doors","type":"Contractor","awarded_to":null,"award_date":null,"value":null,"status":"Not tendered","notes":null},{"package":"Electrical Works","scope":"Electrical","type":"Contractor","awarded_to":null,"award_date":null,"value":null,"status":"Not tendered","notes":null},{"package":"Plumbing Works","scope":"Plumbing","type":"Contractor","awarded_to":null,"award_date":null,"value":null,"status":"Not tendered","notes":null},{"package":"HVAC Works","scope":"HVAC & ventilation","type":"Contractor","awarded_to":null,"award_date":null,"value":null,"status":"Not tendered","notes":null},{"package":"Elevators & Dumbwaiters","scope":"Passenger lifts + service lifts","type":"Supplier","awarded_to":null,"award_date":null,"value":null,"status":"Not tendered","notes":null},{"package":"Stone & Marble Fit-out","scope":"FOH Tier-1 & Tier-2 prime finishes","type":"Contractor","awarded_to":null,"award_date":null,"value":null,"status":"Not tendered","notes":null},{"package":"Pools, Spa & Water Features","scope":"Indoor/outdoor pools, hammam, fountains","type":"Contractor","awarded_to":null,"award_date":null,"value":null,"status":"Not tendered","notes":null},{"package":"Landscape & External Works","scope":"Planting, paving, living walls","type":"Contractor","awarded_to":null,"award_date":null,"value":null,"status":"Not tendered","notes":null},{"package":"Painting & Decoration","scope":"Internal & external finishes","type":"Contractor","awarded_to":null,"award_date":null,"value":null,"status":"Not tendered","notes":null},{"package":"AV, Automation & Security","scope":"Smart home, AV, CCTV, access control","type":"Supplier","awarded_to":null,"award_date":null,"value":null,"status":"Not tendered","notes":null},{"package":"FF&E","scope":"Furniture, fixtures & equipment","type":"Supplier","awarded_to":null,"award_date":null,"value":null,"status":"Not tendered","notes":null}],"prestart":[{"n":1,"phase":"First Stage","principle":"Feasibility Study","ar":"دراسة الجدوى","status":"Not available","resp":"Yasser","act":null,"due":null},{"n":2,"phase":"First Stage","principle":"Planning & Timeline","ar":"Planning & Timeline","status":"To be updated and approved","resp":"Yusuf","act":"P70","due":"2026-11-15"},{"n":3,"phase":"First Stage","principle":"Budgeting","ar":"Budgeting","status":"To be updated and approved","resp":"Yasser","act":"P70","due":"2026-11-15"},{"n":4,"phase":"Second Stage","principle":"License","ar":"الرخصة","status":"Submission in process","resp":"Yasser","act":"P60","due":"2026-12-10"},{"n":5,"phase":"Second Stage","principle":"Design","ar":"Design","status":"Architecture: 80% detail design","resp":"Yusuf","act":"P40","due":"2026-10-26"},{"n":6,"phase":"Second Stage","principle":"Engineering","ar":"Engineering","status":"Consultants not yet appointed","resp":"Yusuf","act":"P50","due":"2026-11-25"},{"n":7,"phase":"Third Stage","principle":"Contracts","ar":"Contracts","status":"Standard contracts to be used","resp":"Abdullah","act":"P90","due":"2026-11-30"},{"n":8,"phase":"Third Stage","principle":"Contractors & Suppliers","ar":"المقاولون والموردون","status":"Vendor list to be developed","resp":"Abdullah","act":"P80","due":"2026-12-08"},{"n":9,"phase":"Third Stage","principle":"Supervision","ar":"Supervision","status":"Project manager to be appointed","resp":"Yusuf","act":"P20","due":"2026-10-31"},{"n":10,"phase":"Third Stage","principle":"Operation Management Manual","ar":"Operation Management Manual","status":"To be developed","resp":"Yusuf","act":null,"due":null}],"occupancy":[{"n":1,"req":"Floor & bathroom tiling","ar":"بلاط البيت والحمامات","act":"D80","actname":"Floor & bathroom tiling","due":"2028-02-15","pct":0,"assess":"Comfortable"},{"n":2,"req":"Internal & external painting","ar":"الدهانات الداخلية والخارجية","act":"D95","actname":"Internal & external painting","due":"2028-02-29","pct":0,"assess":"Warning"},{"n":3,"req":"Aluminium & glazing","ar":"الألمنيوم والزجاج","act":"D60","actname":"Aluminium & glazing","due":"2027-12-15","pct":0,"assess":"Comfortable"},{"n":4,"req":"Internal & external doors","ar":"الأبواب الداخلية والخارجية","act":"D90","actname":"Internal & external doors","due":"2028-01-31","pct":0,"assess":"Comfortable"},{"n":5,"req":"Sanitary fixtures","ar":"الأدوات الصحية","act":"E40","actname":"Sanitary fixtures","due":"2028-02-15","pct":0,"assess":"Comfortable"},{"n":6,"req":"Electrical & lighting","ar":"الكهرباء والإنارة","act":"E30","actname":"Electrical & lighting","due":"2028-01-31","pct":0,"assess":"Comfortable"},{"n":7,"req":"HVAC & ventilation","ar":"التكييف والتهوية","act":"E10","actname":"HVAC & ventilation","due":"2027-12-31","pct":0,"assess":"Comfortable"},{"n":8,"req":"Plumbing & drainage","ar":"أعمال السباكة والصرف","act":"E20","actname":"Plumbing & drainage","due":"2027-11-30","pct":0,"assess":"Comfortable"},{"n":9,"req":"Roof & wet-area waterproofing","ar":"عزل الأسطح والحمامات","act":"D40","actname":"Roof & wet-area waterproofing","due":"2027-09-30","pct":0,"assess":"Comfortable"},{"n":10,"req":"External facades","ar":"الواجهات الخارجية","act":"D50","actname":"External facades (stone)","due":"2027-12-15","pct":0,"assess":"Comfortable"},{"n":11,"req":"Stair & balcony balustrades","ar":"درابزين السلالم والشرفات","act":"F10","actname":"Stair & balcony balustrades","due":"2028-01-31","pct":0,"assess":"Comfortable"},{"n":12,"req":"Fire & life-safety systems","ar":"أنظمة الحريق والسلامة","act":"E50","actname":"Fire & life-safety systems","due":"2028-01-31","pct":0,"assess":"Comfortable"},{"n":13,"req":"Elevators","ar":"المصاعد","act":"E60","actname":"Elevators","due":"2028-01-15","pct":0,"assess":"Comfortable"},{"n":14,"req":"Water tanks & pumps","ar":"الخزانات والمضخات","act":"E70","actname":"Water tanks & pumps","due":"2027-11-30","pct":0,"assess":"Comfortable"},{"n":15,"req":"Entrances & ramps","ar":"المداخل والرامبات","act":"F20","actname":"Entrances & ramps","due":"2028-01-15","pct":0,"assess":"Comfortable"},{"n":16,"req":"Car parking","ar":"مواقف السيارات","act":"F30","actname":"Car parking","due":"2028-01-31","pct":0,"assess":"Comfortable"},{"n":17,"req":"Boundary wall & gates","ar":"السور والبوابات","act":"F40","actname":"Boundary wall & gates","due":"2028-02-15","pct":0,"assess":"Comfortable"},{"n":18,"req":"Pavements & storm drainage","ar":"الأرصفة وتصريف مياه الأمطار","act":"F50","actname":"Pavements & storm drainage","due":"2028-02-29","pct":0,"assess":"Warning"},{"n":19,"req":"Debris removal & site cleaning","ar":"إزالة مخلفات البناء وتنظيف الموقع","act":"G20","actname":"Debris removal & site cleaning","due":"2028-03-05","pct":0,"assess":"Tight margin"},{"n":20,"req":"Compliance with permit & approved drawings","ar":"مطابقة المبنى للرخصة والمخططات المعتمدة","act":"G40","actname":"Compliance with permit & drawings","due":"2028-03-05","pct":0,"assess":"Tight margin"}]}
HVJSON;
    }
}
