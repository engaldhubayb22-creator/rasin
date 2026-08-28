<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('item');                             // حجر الترافرتين الخارجي
            $table->string('activity_code')->nullable();        // النشاط المرتبط (E40)
            $table->string('responsible')->nullable();          // المسؤول (Facade Eng.)
            $table->date('need_by');                            // مطلوب بالموقع
            $table->date('select_by');                          // آخر موعد لاختيار العينة
            $table->text('note')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->index('project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_items');
    }
};
