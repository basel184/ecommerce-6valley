<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('home_sections', function (Blueprint $table) {
            // Update type enum to include categories and brands
            $table->dropColumn('type');
        });
        
        Schema::table('home_sections', function (Blueprint $table) {
            $table->enum('type', ['products', 'banners', 'categories', 'brands'])->default('products')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('home_sections', function (Blueprint $table) {
            $table->dropColumn('type');
        });
        
        Schema::table('home_sections', function (Blueprint $table) {
            $table->enum('type', ['products', 'banners'])->default('products')->after('status');
        });
    }
};
