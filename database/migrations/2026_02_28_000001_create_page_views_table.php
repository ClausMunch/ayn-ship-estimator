<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->string('ip_hash', 64);
            // SQLite does not enforce VARCHAR lengths, so legacy analytics rows
            // can exceed the application's current input caps. TEXT preserves
            // those rows when creating a strict MariaDB/MySQL schema.
            $table->text('user_agent')->nullable();
            $table->text('referrer')->nullable();
            // Preserve legacy SQLite wall-clock values, including local times in
            // daylight-saving gaps that MySQL TIMESTAMP rejects during conversion.
            $table->dateTime('created_at')->useCurrent();

            $table->index('created_at');
            $table->index('ip_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};
