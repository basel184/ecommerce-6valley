<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('home_sections', function (Blueprint $table) {
            if (!Schema::hasColumn('home_sections', 'type')) {
                $table->enum('type', ['products', 'banners'])->default('products')->after('status');
            }
            if (!Schema::hasColumn('home_sections', 'banner_layout')) {
                $table->enum('banner_layout', ['slider', 'grid_1', 'grid_2', 'grid_3'])->default('slider')->after('type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('home_sections', function (Blueprint $table) {
            if (Schema::hasColumn('home_sections', 'banner_layout')) {
                $table->dropColumn('banner_layout');
            }
            if (Schema::hasColumn('home_sections', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
