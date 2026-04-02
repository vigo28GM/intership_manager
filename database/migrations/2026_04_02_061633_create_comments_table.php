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
        Schema::create('comments', function (Blueprint $table) {
            $table->id();

            // Polimorfiskās attiecības
            $table->string('commentable_type');
            $table->unsignedBigInteger('commentable_id');
            $table->index(['commentable_type', 'commentable_id']);

            // Komentāra dati
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('content');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
