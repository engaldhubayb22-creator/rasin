<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('doc');                              // اسم المستند (PR-2026-0042)
            $table->string('type');                             // نوعه: purchase_request/purchase_order/payment_certificate/contract/other
            $table->decimal('amount', 15, 2)->nullable();       // القيمة
            $table->string('submitted_by')->nullable();         // مقدَّم من (اسم)
            $table->foreignId('submitted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('submitted_at')->nullable();           // تاريخ التقديم
            $table->text('note')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->index('project_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
};
