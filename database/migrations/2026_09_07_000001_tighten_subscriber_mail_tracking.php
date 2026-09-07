<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            $table->timestamp('estimate_notification_sent_at')->nullable();
            $table->timestamp('shipped_not_yet_at')->nullable();
            $table->timestamp('delivered_not_yet_at')->nullable();
        });

        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscriber_id')->nullable()->constrained()->nullOnDelete();
            $table->string('recipient');
            $table->string('type', 50)->index();
            $table->string('subject');
            $table->string('message_id')->nullable()->index();
            $table->timestamp('sent_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');

        Schema::table('subscribers', function (Blueprint $table) {
            $table->dropColumn([
                'estimate_notification_sent_at',
                'shipped_not_yet_at',
                'delivered_not_yet_at',
            ]);
        });
    }
};
