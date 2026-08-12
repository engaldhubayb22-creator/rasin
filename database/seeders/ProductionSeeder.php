<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * بذور الإنتاج — تُنشئ الشركة ومستخدم مدير واحد فقط، بدون أي بيانات تجريبية.
 * التشغيل:  php artisan db:seed --class=ProductionSeeder --force
 */
class ProductionSeeder extends Seeder
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

        // غيّر البريد وكلمة المرور فوراً بعد أول دخول
        User::firstOrCreate(['email' => env('ADMIN_EMAIL', 'admin@rasin.sa')], [
            'company_id' => $company->id,
            'name' => env('ADMIN_NAME', 'مدير النظام'),
            'password' => Hash::make(env('ADMIN_PASSWORD', 'ChangeMe#2026')),
            'role' => 'admin',
            'job_title' => 'مدير النظام',
            'is_active' => true,
        ]);
    }
}
