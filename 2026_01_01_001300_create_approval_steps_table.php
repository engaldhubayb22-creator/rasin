<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_id')->constrained()->cascadeOnDelete();
            $table->string('role_label');                       // الدور في الخطوة (مدير المشروع)
            $table->string('approver_name')->nullable();        // اسم المُعتمد
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending');       // pending/approved/returned/rejected
            $table->date('decided_at')->nullable();             // تاريخ القرار
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->index(['approval_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_steps');
    }
};
