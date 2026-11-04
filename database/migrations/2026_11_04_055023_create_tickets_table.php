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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject');
            $table->string('category', 50);
            $table->string('priority', 20)->default('medium');
            $table->string('status', 20)->default('open');
            $table->text('description');
            $table->timestamp('last_reply_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('priority');
            $table->index('category');
            $table->index('assignee_id');
            $table->index('user_id');
            $table->index('last_reply_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
