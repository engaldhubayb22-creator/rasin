<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('code')->nullable();               // CL-006-084
            $table->string('title');                          // البند
            $table->text('note')->nullable();                 // ملاحظة تحت البند
            $table->string('department')->nullable();         // الجهة: projects_mgmt/procurement/executive
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete(); // المسؤول (يربطه بمهام العضو)
            $table->date('due_date')->nullable();             // الاستحقاق
            $table->string('status')->default('in_progress'); // urgent/in_progress/pending/completed
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requirements');
    }
};
