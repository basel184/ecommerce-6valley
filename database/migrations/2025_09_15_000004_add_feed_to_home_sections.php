<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('home_sections', function (Blueprint $table) {
            if (!Schema::hasColumn('home_sections', 'feed_type')) {
                $table->string('feed_type')->default('manual')->after('banner_layout');
            }
            if (!Schema::hasColumn('home_sections', 'feed_category_id')) {
                // Nullable category source when feed_type = 'category'
                $table->unsignedBigInteger('feed_category_id')->nullable()->after('feed_type');
                // Add FK only if categories table exists
                if (Schema::hasTable('categories')) {
                    $table->foreign('feed_category_id')->references('id')->on('categories')->nullOnDelete();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('home_sections', function (Blueprint $table) {
            if (Schema::hasColumn('home_sections', 'feed_category_id')) {
                try { $table->dropForeign(['feed_category_id']); } catch (\Throwable $e) {}
                $table->dropColumn('feed_category_id');
            }
            if (Schema::hasColumn('home_sections', 'feed_type')) {
                $table->dropColumn('feed_type');
            }
        });
    }
};
