<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('model_variant_id')->constrained()->cascadeOnDelete();
            $table->date('ship_date');
            $table->integer('order_range_start');
            $table->integer('order_range_end');
            $table->timestamp('scraped_at')->nullable();
            $table->timestamps();

            $table->unique(['model_variant_id', 'ship_date', 'order_range_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_batches');
    }
};
