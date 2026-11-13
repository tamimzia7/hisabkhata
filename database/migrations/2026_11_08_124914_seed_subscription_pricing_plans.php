<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The pricing catalogue for every independent product.
     *
     * @var list<array{slug: string, name: string, product: string, billing_cycle: string, price: float, duration_days: int, features: array<string, mixed>}>
     */
    private const PLANS = [
        [
            'slug' => 'student-trial',
            'name' => 'Meal Management — 30-Day Free Trial',
            'product' => 'student',
            'billing_cycle' => 'trial',
            'price' => 0,
            'duration_days' => 30,
            'features' => ['trial' => true],
        ],
        [
            'slug' => 'student-monthly',
            'name' => 'Meal Management — Monthly',
            'product' => 'student',
            'billing_cycle' => 'monthly',
            'price' => 50,
            'duration_days' => 30,
            'features' => ['max_messes' => 1],
        ],
        [
            'slug' => 'student-yearly',
            'name' => 'Meal Management — Yearly',
            'product' => 'student',
            'billing_cycle' => 'yearly',
            'price' => 500,
            'duration_days' => 365,
            'features' => ['max_messes' => 1],
        ],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (self::PLANS as $plan) {
            $values = [
                'name' => $plan['name'],
                'product' => $plan['product'],
                'billing_cycle' => $plan['billing_cycle'],
                'price' => $plan['price'],
                'currency' => 'BDT',
                'duration_days' => $plan['duration_days'],
                'features' => json_encode($plan['features']),
                'is_active' => true,
                'updated_at' => now(),
            ];

            if (DB::table('subscription_plans')->where('slug', $plan['slug'])->exists()) {
                DB::table('subscription_plans')->where('slug', $plan['slug'])->update($values);
            } else {
                DB::table('subscription_plans')->insert($values + [
                    'slug' => $plan['slug'],
                    'created_at' => now(),
                ]);
            }
        }

        // Keep legacy subscriptions valid, but never show the retired free plan
        // in the paid plan selector.
        DB::table('subscription_plans')
            ->where('slug', 'student-mess')
            ->update(['billing_cycle' => 'legacy', 'updated_at' => now()]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Pricing rows are intentionally retained: deleting a plan would break
        // historical subscription and payment references.
    }
};
