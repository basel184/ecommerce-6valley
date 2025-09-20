<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('home_sections', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->unsignedInteger('show_limit')->default(8);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('status')->default(true);
            // New: type and layout for flexible sections
            $table->enum('type', ['products', 'banners'])->default('products');
            $table->enum('banner_layout', ['slider', 'grid_1', 'grid_2', 'grid_3'])->default('slider');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_sections');
    }
};
