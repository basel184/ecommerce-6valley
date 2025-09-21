<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->boolean('is_abandoned')->default(false)->after('is_guest');
            $table->timestamp('abandoned_at')->nullable()->after('is_abandoned');
            $table->integer('reminder_sent')->default(0)->after('abandoned_at');
            $table->timestamp('last_reminder_sent_at')->nullable()->after('reminder_sent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropColumn(['is_abandoned', 'abandoned_at', 'reminder_sent', 'last_reminder_sent_at']);
        });
    }
};
