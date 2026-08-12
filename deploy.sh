#!/usr/bin/env bash
# ============================================================
#  سكربت النشر — الصقه في Laravel Forge (Deploy Script)
#  أو نفّذه يدوياً داخل مجلد المشروع على السيرفر.
# ============================================================
set -e

echo "→ سحب أحدث كود…"
git pull origin main

echo "→ تثبيت الحزم (بدون تطوير، مُحسّن)…"
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

echo "→ ترحيل قاعدة البيانات (آمن — لا يحذف بيانات)…"
php artisan migrate --force

echo "→ تحسين الأداء (كاش الإعدادات/المسارات/الواجهات)…"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✔ اكتمل النشر."
