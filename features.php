<?php

/*
|--------------------------------------------------------------------------
|  مفاتيح تشغيل الوحدات (Feature Flags)
|--------------------------------------------------------------------------
|  الوحدات المخفية موجودة في الكود لكنها لا تظهر في الواجهة حتى تُفعّل.
|  لتفعيل وحدة: غيّر القيمة إلى true هنا، أو اضبط المتغيّر في ملف .env
|  (مثال: FEATURE_PROCUREMENT=true) ثم أعد النشر.
*/

return [
    'procurement' => env('FEATURE_PROCUREMENT', false), // المشتريات — مخفية مؤقتاً
    'contracts' => env('FEATURE_CONTRACTS', false),     // العقود — مخفية مؤقتاً
    'administration' => env('FEATURE_ADMINISTRATION', false), // الإدارة — مخفية مؤقتاً
    'settings' => env('FEATURE_SETTINGS', false),       // الإعدادات — مخفية مؤقتاً
];
