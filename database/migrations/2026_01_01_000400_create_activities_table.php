<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('activity_code')->nullable();     // A10, A20...
            $table->string('phase')->nullable();             // المرحلة
            $table->string('name');                          // اسم النشاط
            $table->string('name_en')->nullable();           // الاسم بالإنجليزية (اختياري)
            $table->unsignedSmallInteger('duration_days')->nullable();
            $table->date('planned_start')->nullable();
            $table->date('planned_finish')->nullable();
            $table->date('actual_start')->nullable();
            $table->date('actual_finish')->nullable();
            $table->unsignedTinyInteger('planned_percent')->default(0);
            $table->unsignedTinyInteger('actual_percent')->default(0);
            $table->boolean('is_critical')->default(false);
            $table->string('status')->default('not_started'); // not_started/in_progress/completed/delayed
            $table->unsignedInteger('order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
