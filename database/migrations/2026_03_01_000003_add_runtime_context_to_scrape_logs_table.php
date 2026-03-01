<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scrape_logs', function (Blueprint $table) {
            $table->text('runtime_context')->nullable()->after('error_message');
        });
    }

    public function down(): void
    {
        Schema::table('scrape_logs', function (Blueprint $table) {
            $table->dropColumn('runtime_context');
        });
    }
};
