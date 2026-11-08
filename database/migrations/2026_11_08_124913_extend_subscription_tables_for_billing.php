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
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->string('billing_cycle', 20)->default('monthly')->after('product')->index();
            $table->string('currency', 3)->default('BDT')->after('price');
        });

        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->string('billing_cycle', 20)->nullable()->after('subscription_plan_id')->index();
            $table->unsignedBigInteger('subscription_trial_id')->nullable()->after('subscription_plan_id');
            $table->unsignedBigInteger('payment_id')->nullable()->after('subscription_plan_id');
            $table->timestamp('suspended_at')->nullable()->after('cancelled_at');
            $table->unsignedBigInteger('suspended_by')->nullable()->after('suspended_at');
            $table->string('suspension_reason', 255)->nullable()->after('suspended_by');

            // A single verified payment can never activate more than one subscription.
            $table->unique('payment_id');
            $table->unique(['user_id', 'subscription_trial_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'subscription_trial_id']);
            $table->dropUnique(['payment_id']);
            $table->dropIndex(['billing_cycle']);
            $table->dropColumn([
                'billing_cycle',
                'subscription_trial_id',
                'payment_id',
                'suspended_at',
                'suspended_by',
                'suspension_reason',
            ]);
        });

        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropIndex(['billing_cycle']);
            $table->dropColumn(['billing_cycle', 'currency']);
        });
    }
};
