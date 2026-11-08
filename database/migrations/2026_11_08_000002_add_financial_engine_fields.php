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
        Schema::table('meal_entries', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->after('note')->index();
            $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
        });

        Schema::table('bazar_records', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->after('receipt_path')->index();
        });

        Schema::table('mess_deposits', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->after('notes')->index();
        });

        Schema::table('mess_expenses', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->after('receipt_path')->index();
            $table->boolean('is_personal')->default(false)->after('is_meal_related')->index();
            $table->foreignId('mess_member_id')->nullable()->after('created_by')->constrained('mess_members')->nullOnDelete();
        });

        Schema::table('mess_transactions', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->after('meta')->index();
        });

        Schema::table('monthly_settlements', function (Blueprint $table) {
            $table->decimal('total_meal_cost', 12, 2)->default(0)->after('total_meals');
            $table->decimal('total_refunds', 12, 2)->default(0)->after('total_deposits');
            $table->decimal('total_adjustments', 12, 2)->default(0)->after('total_refunds');
            $table->decimal('total_personal_expenses', 12, 2)->default(0)->after('total_other_expenses');
            $table->decimal('total_outstanding_dues', 12, 2)->default(0)->after('total_surplus');
            $table->decimal('total_receivable', 12, 2)->default(0)->after('total_outstanding_dues');
        });

        Schema::table('settlement_details', function (Blueprint $table) {
            $table->decimal('opening_balance', 12, 2)->default(0)->after('mess_member_id');
            $table->decimal('refunds', 12, 2)->default(0)->after('deposits');
            $table->decimal('personal_expenses', 12, 2)->default(0)->after('other_allocated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settlement_details', function (Blueprint $table) {
            $table->dropColumn(['opening_balance', 'refunds', 'personal_expenses']);
        });

        Schema::table('monthly_settlements', function (Blueprint $table) {
            $table->dropColumn([
                'total_meal_cost',
                'total_refunds',
                'total_adjustments',
                'total_personal_expenses',
                'total_outstanding_dues',
                'total_receivable',
            ]);
        });

        Schema::table('mess_transactions', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('mess_expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('mess_member_id');
            $table->dropColumn(['status', 'is_personal']);
        });

        Schema::table('mess_deposits', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('bazar_records', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('meal_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('updated_by');
            $table->dropColumn('status');
        });
    }
};
