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
        Schema::create('reverse_transfer_statuses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reverse_transfer_id');
            $table->enum('status', ['pending', 'approved', 'rejected', 'processed', 'completed']);
            $table->string('changed_by')->nullable();
            $table->unsignedBigInteger('changed_by_id')->nullable();
            $table->text('notes')->nullable();
            $table->string('previous_status')->nullable();
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('reverse_transfer_id')->references('id')->on('reverse_transfers')->onDelete('cascade');
            $table->foreign('changed_by_id')->references('id')->on('users')->onDelete('set null');
            
            // Indexes
            $table->index('reverse_transfer_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reverse_transfer_statuses');
    }
};
