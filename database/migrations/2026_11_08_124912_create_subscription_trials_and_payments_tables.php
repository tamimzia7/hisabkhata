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
        Schema::create('subscription_trials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product', 30)->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            // Permanent, one-per-product trial history. This is the database-level
            // guarantee that a user can never claim the same trial twice.
            $table->unique(['user_id', 'product']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subscription_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('transaction_id', 64)->unique();
            $table->string('gateway', 30)->default('sslcommerz');
            $table->string('gateway_transaction_id', 100)->nullable()->unique();
            $table->string('product', 30)->index();
            $table->string('billing_cycle', 20);
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('BDT');
            $table->string('status', 20)->default('pending')->index();
            $table->string('gateway_status', 50)->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->string('gateway_page_url', 2048)->nullable();
            $table->json('gateway_response')->nullable();
            $table->json('validation_response')->nullable();
            $table->string('failure_reason', 255)->nullable();
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('ipn_verified_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['product', 'status']);
            $table->index(['status', 'paid_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('subscription_trials');
    }
};
