<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * The pricing catalogue for every independent product.
     *
     * @var list<array{slug: string, name: string, product: string, billing_cycle: string, price: float, duration_days: int, features: array<string, mixed>}>
     */
    private const PLANS = [
        ['slug' => 'student-trial', 'name' => 'Meal Management — 30-Day Free Trial', 'product' => SubscriptionPlan::PRODUCT_STUDENT, 'billing_cycle' => SubscriptionPlan::BILLING_CYCLE_TRIAL, 'price' => 0, 'duration_days' => 30, 'features' => ['trial' => true]],
        ['slug' => 'student-monthly', 'name' => 'Meal Management — Monthly', 'product' => SubscriptionPlan::PRODUCT_STUDENT, 'billing_cycle' => SubscriptionPlan::BILLING_CYCLE_MONTHLY, 'price' => 50, 'duration_days' => 30, 'features' => ['max_messes' => 1]],
        ['slug' => 'student-yearly', 'name' => 'Meal Management — Yearly', 'product' => SubscriptionPlan::PRODUCT_STUDENT, 'billing_cycle' => SubscriptionPlan::BILLING_CYCLE_YEARLY, 'price' => 500, 'duration_days' => 365, 'features' => ['max_messes' => 1]],
    ];

    /**
     * Seed the pricing catalogue.
     */
    public function run(): void
    {
        foreach (self::PLANS as $plan) {
            SubscriptionPlan::query()->updateOrCreate(
                ['slug' => $plan['slug']],
                [
                    'name' => $plan['name'],
                    'product' => $plan['product'],
                    'billing_cycle' => $plan['billing_cycle'],
                    'price' => $plan['price'],
                    'currency' => SubscriptionPlan::CURRENCY_BDT,
                    'duration_days' => $plan['duration_days'],
                    'features' => $plan['features'],
                    'is_active' => true,
                ],
            );
        }

        SubscriptionPlan::query()
            ->where('slug', 'student-mess')
            ->update(['billing_cycle' => SubscriptionPlan::BILLING_CYCLE_LEGACY]);
    }
}
