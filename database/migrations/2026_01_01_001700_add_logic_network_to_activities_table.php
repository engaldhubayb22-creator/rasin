<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->unsignedTinyInteger('level')->default(2)->after('actual_percent'); // 0=مرحلة، 1/2=نشاط
            // شبكة المنطق — حتى 3 أنشطة سابقة (ID + النوع FS/SS/FF/SF + الإزاحة)
            $table->string('pred1', 20)->nullable()->after('level');
            $table->string('type1', 2)->nullable()->after('pred1');
            $table->integer('lag1')->nullable()->after('type1');
            $table->string('pred2', 20)->nullable()->after('lag1');
            $table->string('type2', 2)->nullable()->after('pred2');
            $table->integer('lag2')->nullable()->after('type2');
            $table->string('pred3', 20)->nullable()->after('lag2');
            $table->string('type3', 2)->nullable()->after('pred3');
            $table->integer('lag3')->nullable()->after('type3');
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn(['level', 'pred1', 'type1', 'lag1', 'pred2', 'type2', 'lag2', 'pred3', 'type3', 'lag3']);
        });
    }
};
