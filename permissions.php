<?php

/*
|--------------------------------------------------------------------------
|  كتالوج الصلاحيات (module.action)
|--------------------------------------------------------------------------
|  كل صلاحية تُكتب بصيغة module.action — مثل projects.view أو approvals.approve.
|  «modules» يعرّف الأفعال المتاحة لكل وحدة (لبناء مصفوفة الصلاحيات).
|  «defaults» هي التوزيعة الافتراضية لكل دور، تُزرع في جدول role_permissions
|  أول مرة، ثم يعدّلها المدير من صفحة «الأدوار والصلاحيات».
|  الدور admin يملك كل الصلاحيات دائماً (لا يحتاج إدخالات).
*/

return [

    'modules' => [
        'projects' => ['view', 'create', 'edit', 'delete'],
        'schedule' => ['view', 'edit'],
        'checklist' => ['view', 'edit'],
        'requirements' => ['view', 'edit'],
        'procurement' => ['view', 'edit'],
        'approvals' => ['view', 'approve'],
        'drawings' => ['view', 'edit'],
        'finance' => ['view', 'pay'],
        'reports' => ['view'],
        'users' => ['view', 'manage'],
    ],

    'defaults' => [

        // المدير التنفيذي — إشراف واعتماد شامل بلا إدارة مستخدمين
        'executive' => [
            'projects.view', 'projects.create', 'projects.edit',
            'schedule.view', 'checklist.view', 'requirements.view',
            'procurement.view', 'approvals.view', 'approvals.approve',
            'drawings.view', 'finance.view', 'finance.pay', 'reports.view',
        ],

        // مدير المشروع — تشغيل كامل داخل المشاريع + اعتماد
        'project_manager' => [
            'projects.view', 'projects.edit',
            'schedule.view', 'schedule.edit',
            'checklist.view', 'checklist.edit',
            'requirements.view', 'requirements.edit',
            'procurement.view', 'procurement.edit',
            'approvals.view', 'approvals.approve',
            'drawings.view', 'drawings.edit', 'reports.view',
        ],

        // مهندس المشروع — تنفيذ فني بلا اعتماد ولا مالية
        'engineer' => [
            'projects.view',
            'schedule.view', 'schedule.edit',
            'checklist.view', 'checklist.edit',
            'requirements.view', 'requirements.edit',
            'procurement.view',
            'drawings.view', 'drawings.edit',
        ],

        // المالية — مالية واعتماد المستخلصات والتقارير
        'finance' => [
            'projects.view', 'finance.view', 'finance.pay',
            'approvals.view', 'approvals.approve',
            'procurement.view', 'reports.view',
        ],
    ],
];
