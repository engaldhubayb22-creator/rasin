<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklist_template_items', function (Blueprint $table) {
            $table->id();
            $table->string('phase');                          // المرحلة
            $table->string('title');                          // البند
            $table->boolean('is_mandatory')->default(true);   // إلزامي / اختياري
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->index('order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_template_items');
    }
};
