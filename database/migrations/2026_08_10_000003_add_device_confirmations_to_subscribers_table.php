<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            $table->timestamp('shipped_confirmation_sent_at')->nullable();
            $table->timestamp('shipped_confirmed_at')->nullable();
            $table->timestamp('delivered_confirmation_sent_at')->nullable();
            $table->timestamp('delivered_confirmed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            $table->dropColumn([
                'shipped_confirmation_sent_at',
                'shipped_confirmed_at',
                'delivered_confirmation_sent_at',
                'delivered_confirmed_at',
            ]);
        });
    }
};
