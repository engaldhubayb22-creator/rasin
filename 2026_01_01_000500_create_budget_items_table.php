<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('item_code')->nullable();          // B10, B20...
            $table->string('category')->nullable();           // البند الرئيسي (أعمال ترابية، خرسانة...)
            $table->string('name');                           // وصف البند
            $table->string('name_en')->nullable();            // الوصف بالإنجليزية (اختياري)
            $table->decimal('budgeted_amount', 15, 2)->default(0);   // المعتمد / المخطط
            $table->decimal('committed_amount', 15, 2)->default(0);  // المرتبط (عقود/أوامر شراء)
            $table->decimal('actual_amount', 15, 2)->default(0);     // المصروف الفعلي
            $table->unsignedInteger('order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_items');
    }
};
