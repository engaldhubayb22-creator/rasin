<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');                              // نسخة شهر 4
            $table->string('status')->default('pending');        // pending / approved / rejected
            $table->date('period_start')->nullable();            // بداية بيانات الجدول
            $table->date('period_finish')->nullable();           // نهاية بيانات الجدول
            $table->string('source_file')->nullable();           // اسم الملف المرفوع (mpp/xlsx/pdf)
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_versions');
    }
};
