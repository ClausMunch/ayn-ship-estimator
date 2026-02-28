<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estimation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('model_variant_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('order_prefix');
            $table->string('result_type', 20);
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
            $table->index('model_variant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estimation_logs');
    }
};
