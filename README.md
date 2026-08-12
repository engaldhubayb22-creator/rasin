# رسين — نظام متابعة المشاريع

نظام Laravel 11 لمتابعة المشاريع الإنشائية، **ثنائي اللغة (عربي/إنجليزي)**، مبني حول المشروع كمحور.

الشركة: **شركة رسين للتطوير العقاري**.

## المميزات (حتى المرحلة ٢)
- مصادقة وأدوار مبسّطة + تعدد الشركات.
- **المشاريع**: قائمة/إضافة/تعديل/عرض + بحث وفلترة.
- **مساحة عمل المشروع** بتبويبات: نظرة عامة · المهام · الجدول الزمني · الفريق (وتبويبات قادمة).
- **المهام**: إضافة، إسناد، أولوية، تغيير الحالة، حذف.
- **الجدول الزمني**: أنشطة بنسبة مخطط% / فعلي% + الانحراف + المسار الحرج + الحالة.
- **الفريق**: إضافة/إزالة أعضاء المشروع مع الدور.
- **مهامي**: كل المهام المسندة للمستخدم عبر جميع المشاريع.
- **اللغتان**: مبدّل عربي↔إنجليزي يقلب الواجهة RTL/LTR (ملفات `lang/ar` و `lang/en`).
- لوحة تحكم بإحصائيات، بيانات تجريبية جاهزة.

## المتطلبات
PHP 8.2+ · Composer · MySQL/MariaDB (أو SQLite للتطوير).

## التشغيل محلياً (SQLite سريع)
```bash
composer install
cp .env.example .env
# في .env: DB_CONNECTION=sqlite (واحذف بقية أسطر DB_)
touch database/database.sqlite
php artisan key:generate
php artisan migrate --seed
php artisan serve
```
افتح http://127.0.0.1:8000 — الدخول: `admin@rasin.sa` / `password`
(مستخدمون آخرون: `pm@rasin.sa` و `eng@rasin.sa`)

## النشر على cPanel (MySQL)
```bash
composer install --no-dev --optimize-autoloader
# اضبط .env: DB_CONNECTION=mysql وبيانات القاعدة
php artisan key:generate
php artisan migrate --seed --force
php artisan optimize:clear && php artisan config:cache && php artisan route:cache
chmod -R 775 storage bootstrap/cache
```
اجعل مجلد الدومين يشير إلى `public/`.

## اللغتان
- كل النصوص عبر `lang/ar/app.php` و `lang/en/app.php` (150 مفتاح متطابق).
- التبديل من الزر أعلى الواجهة، ويُحفظ في الجلسة عبر `SetLocale` middleware.
- الحقول المترجَمة (مثل اسم النشاط) لها عمود `name_en` اختياري.

## البنية
```
app/Http/Controllers/  Auth, Dashboard, Project, Task, Activity, ProjectMember, MyTasks
app/Models/            User, Company, Project, Task, Activity, ProjectMember
app/Http/Middleware/   SetLocale, EnsureUserHasRole
database/migrations/   companies, users, projects, project_members, tasks, activities
lang/{ar,en}/app.php   ملفات الترجمة
resources/views/       layout, auth, dashboard, my-tasks, projects/* (show = مساحة العمل)
routes/web.php         كل المسارات + تبديل اللغة
```

## القادم (حسب المخطط v1.1)
المرحلة ٣: المخططات والمتطلبات والكميات/التكلفة · المرحلة ٤: الاعتمادات والإشعارات · المرحلة ٥: المشتريات (طلبات/أوامر/موردون + التوريدات طويلة الأمد + المناقصات).
