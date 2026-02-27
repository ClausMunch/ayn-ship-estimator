<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scrape_logs', function (Blueprint $table) {
            $table->id();
            $table->string('status'); // 'success' or 'failed'
            $table->integer('records_found')->default(0);
            $table->integer('records_new')->default(0);
            $table->text('error_message')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scrape_logs');
    }
};
