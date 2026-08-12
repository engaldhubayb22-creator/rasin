<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_version_id')->constrained()->cascadeOnDelete();
            $table->string('wbs')->nullable();                   // 1، 1.1، 2.10
            $table->unsignedTinyInteger('level')->default(2);    // 0 مشروع، 1 مرحلة، 2 نشاط
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->date('planned_start')->nullable();
            $table->date('planned_finish')->nullable();
            $table->date('actual_start')->nullable();
            $table->date('actual_finish')->nullable();
            $table->unsignedTinyInteger('percent')->default(0);  // نسبة الإنجاز
            $table->boolean('is_critical')->default(false);      // على المسار الحرج
            $table->integer('delay_days')->default(0);           // التأخر بالأيام (+ متأخر)
            $table->string('status')->default('not_started');    // not_started/in_progress/completed/delayed
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->index(['schedule_version_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_activities');
    }
};
