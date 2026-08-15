<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('phase');                          // المرحلة
            $table->string('title');                          // البند
            $table->boolean('is_mandatory')->default(true);   // إلزامي / اختياري
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete(); // المسؤول
            $table->date('planned_date')->nullable();         // التاريخ المخطط
            $table->date('actual_date')->nullable();          // تاريخ الإنجاز الفعلي
            $table->string('status')->default('not_started'); // not_started/in_progress/pending_approval/completed/not_applicable
            $table->string('evidence')->nullable();           // المرفق / دليل الإنجاز
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete(); // الشخص المعتمد
            $table->text('notes')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->index(['project_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_items');
    }
};
