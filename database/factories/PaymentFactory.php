<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $plan = SubscriptionPlan::query()->first() ?? SubscriptionPlan::query()->create([
            'slug' => 'payment-factory-plan',
            'name' => 'Payment Factory Plan',
            'product' => SubscriptionPlan::PRODUCT_STUDENT,
            'billing_cycle' => SubscriptionPlan::BILLING_CYCLE_MONTHLY,
            'price' => 50,
            'currency' => SubscriptionPlan::CURRENCY_BDT,
            'duration_days' => 30,
            'is_active' => true,
        ]);

        return [
            'user_id' => User::factory(),
            'subscription_plan_id' => $plan->getKey(),
            'transaction_id' => 'HK'.Str::upper(Str::random(20)),
            'gateway' => Payment::GATEWAY_SSLCOMMERZ,
            'product' => $plan->product,
            'billing_cycle' => $plan->billing_cycle,
            'amount' => $plan->price,
            'currency' => Payment::CURRENCY_BDT,
            'status' => Payment::STATUS_PENDING,
        ];
    }
}
