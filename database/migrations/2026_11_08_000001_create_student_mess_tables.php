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
        Schema::create('messes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('address')->nullable();
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->string('billing_month', 7)->nullable();
            $table->decimal('monthly_budget', 12, 2)->default(0);
            $table->string('contact_name')->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->string('contact_email')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
        });

        Schema::create('mess_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mess_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('photo_path')->nullable();
            $table->date('joining_date')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->string('role', 20)->default('member')->index();
            $table->string('emergency_contact')->nullable();
            $table->json('permissions')->nullable();
            $table->timestamps();

            $table->index(['mess_id', 'user_id']);
            $table->index(['mess_id', 'status']);
        });

        Schema::create('mess_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mess_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email');
            $table->string('role', 20)->default('member');
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->index(['mess_id', 'email']);
        });

        Schema::create('mess_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mess_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 20)->default('cash');
            $table->decimal('opening_balance', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['mess_id', 'is_active']);
        });

        Schema::create('meal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mess_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mess_member_id')->constrained('mess_members')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('entry_date')->index();
            $table->decimal('breakfast', 8, 2)->default(0);
            $table->decimal('lunch', 8, 2)->default(0);
            $table->decimal('dinner', 8, 2)->default(0);
            $table->decimal('total_meals', 8, 2)->storedAs('breakfast + lunch + dinner');
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['mess_id', 'entry_date']);
            $table->index(['mess_id', 'mess_member_id', 'entry_date'], 'meal_entries_mess_member_date_index');
        });

        Schema::create('bazar_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mess_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchased_by_member_id')->nullable()->constrained('mess_members')->nullOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('mess_accounts')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('purchase_date')->index();
            $table->string('category', 60)->default('general');
            $table->string('payment_source', 20)->default('cash');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('receipt_path')->nullable();
            $table->timestamps();

            $table->index(['mess_id', 'purchase_date']);
        });

        Schema::create('bazar_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bazar_record_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->string('unit', 20)->default('pcs');
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('total_price', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('mess_deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mess_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mess_member_id')->constrained('mess_members')->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('mess_accounts')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('deposit_date')->index();
            $table->decimal('amount', 12, 2);
            $table->string('type', 20)->default('deposit');
            $table->string('payment_method', 20)->default('cash');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['mess_id', 'deposit_date']);
            $table->index(['mess_id', 'mess_member_id']);
        });

        Schema::create('mess_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mess_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('mess_accounts')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('expense_date')->index();
            $table->string('title');
            $table->decimal('amount', 12, 2);
            $table->string('category', 60)->default('other');
            $table->boolean('is_meal_related')->default(false);
            $table->string('allocation_method', 20)->default('equal');
            $table->text('notes')->nullable();
            $table->string('receipt_path')->nullable();
            $table->timestamps();

            $table->index(['mess_id', 'expense_date']);
        });

        Schema::create('mess_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mess_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('mess_accounts')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 30)->index();
            $table->string('direction', 10);
            $table->decimal('amount', 12, 2);
            $table->date('transaction_date')->index();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('description')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['mess_id', 'transaction_date']);
            $table->index(['reference_type', 'reference_id']);
        });

        Schema::create('monthly_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mess_id')->constrained()->cascadeOnDelete();
            $table->string('month', 7);
            $table->unsignedInteger('version')->default(1);
            $table->string('status', 20)->default('draft')->index();
            $table->decimal('total_deposits', 12, 2)->default(0);
            $table->decimal('total_bazar', 12, 2)->default(0);
            $table->decimal('total_meal_expenses', 12, 2)->default(0);
            $table->decimal('total_other_expenses', 12, 2)->default(0);
            $table->decimal('total_meals', 12, 2)->default(0);
            $table->decimal('meal_rate', 12, 4)->default(0);
            $table->decimal('total_surplus', 12, 2)->default(0);
            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reopened_at')->nullable();
            $table->foreignId('reopened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reopen_reason')->nullable();
            $table->timestamps();

            $table->unique(['mess_id', 'month', 'version']);
            $table->index(['mess_id', 'month', 'status']);
        });

        Schema::create('settlement_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_settlement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mess_member_id')->constrained('mess_members')->cascadeOnDelete();
            $table->decimal('meals', 12, 2)->default(0);
            $table->decimal('meal_cost', 12, 2)->default(0);
            $table->decimal('deposits', 12, 2)->default(0);
            $table->decimal('other_allocated', 12, 2)->default(0);
            $table->decimal('adjustments', 12, 2)->default(0);
            $table->decimal('balance', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['monthly_settlement_id', 'mess_member_id']);
        });

        Schema::create('mess_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mess_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 60)->index();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('meta')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['mess_id', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mess_audit_logs');
        Schema::dropIfExists('settlement_details');
        Schema::dropIfExists('monthly_settlements');
        Schema::dropIfExists('mess_transactions');
        Schema::dropIfExists('mess_expenses');
        Schema::dropIfExists('mess_deposits');
        Schema::dropIfExists('bazar_items');
        Schema::dropIfExists('bazar_records');
        Schema::dropIfExists('meal_entries');
        Schema::dropIfExists('mess_accounts');
        Schema::dropIfExists('mess_invitations');
        Schema::dropIfExists('mess_members');
        Schema::dropIfExists('messes');
    }
};
