<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->foreignId('model_variant_id')->constrained()->cascadeOnDelete();
            $table->integer('order_prefix');
            $table->date('last_estimated_date')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('verification_token', 64)->unique();
            $table->string('unsubscribe_token', 64)->unique();
            $table->timestamps();

            $table->unique(['email', 'model_variant_id', 'order_prefix']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscribers');
    }
};
