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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])
                  ->default('pending')
                  ->index();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])
                  ->default('medium')
                  ->index();
            $table->foreignId('owner_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->foreignId('assignee_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->date('due_date')->nullable()->index();
            $table->timestamp('completed_at')->nullable();
            $table->decimal('estimated_hours', 8, 2)->nullable();
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Composite indexes for common queries
            $table->index(['owner_id', 'status']);
            $table->index(['assignee_id', 'status']);
            $table->index(['status', 'due_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
