<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('model_variants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 100)->unique();
            $table->integer('display_order')->default(0);
            $table->json('color_config');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_variants');
    }
};
