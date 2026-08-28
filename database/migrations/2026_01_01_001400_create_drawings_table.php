<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drawings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code');                             // A-101
            $table->string('title');                            // مخطط القبو
            $table->string('discipline');                       // architectural/structural/mep/...
            $table->string('revision')->default('R00');         // الإصدار
            $table->date('drawing_date')->nullable();           // تاريخ الإصدار
            $table->string('status')->default('draft');         // draft/under_review/approved
            $table->text('note')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->index('project_id');
            $table->index('discipline');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drawings');
    }
};
