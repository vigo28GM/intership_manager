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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            
            // User who performed the action (nullable for system actions)
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Action type (e.g., 'create', 'update', 'delete', 'login', 'apply_internship')
            $table->string('action_type', 50);
            
            // Human-readable description
            $table->text('description');
            
            // Polymorphic relation to any model
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->index(['model_type', 'model_id']);
            
            // Additional properties in JSON format
            $table->json('properties')->nullable();
            
            // Request information
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('url')->nullable();
            $table->string('method', 10)->nullable();
            
            // Status (success, failed, error)
            $table->string('status', 20)->default('success');
            
            // Optional error message
            $table->text('error_message')->nullable();
            
            $table->timestamps();
            
            // Index for filtering by user and action type
            $table->index(['user_id', 'action_type']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
