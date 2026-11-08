<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Seed the Student/Mess subscription plan.
     */
    public function run(): void
    {
        SubscriptionPlan::updateOrCreate(
            ['slug' => 'student-mess'],
            [
                'name' => 'HisabKhata Student',
                'product' => SubscriptionPlan::PRODUCT_STUDENT,
                'price' => 0,
                'duration_days' => 30,
                'features' => [
                    'max_messes' => 1,
                    'bullets' => [
                        'student.subscription.bullets.meals',
                        'student.subscription.bullets.bazar',
                        'student.subscription.bullets.settlement',
                        'student.subscription.bullets.reports',
                    ],
                ],
                'is_active' => true,
            ],
        );
    }
}
