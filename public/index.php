<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// تحديد إن كان التطبيق في وضع الصيانة...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// تحميل الـ Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// تشغيل التطبيق ومعالجة الطلب...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
