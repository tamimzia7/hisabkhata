<?php

namespace App\Services;

use App\Models\Mess;
use App\Models\MessMember;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Facades\Route;

class ProductNavigationService
{
    /**
     * Navigation definitions for products that have an implemented workspace.
     *
     * @var array<string, array{label: string, navLabel: string, icon: string, dashboard: string, main: list<array{label: string, icon: string, route: string, pattern: string, ability?: string}>, settings: list<array{label: string, icon: string, route: string, pattern: string, ownerOnly?: bool}>}>
     */
    private const PRODUCTS = [
        SubscriptionPlan::PRODUCT_STUDENT => [
            'label' => 'subscription.products.student',
            'navLabel' => 'student.nav.module_link',
            'icon' => 'utensils-crossed',
            'dashboard' => 'student.dashboard',
            'main' => [
                ['label' => 'student.nav.dashboard', 'icon' => 'layout-dashboard', 'route' => 'student.dashboard', 'pattern' => 'student.dashboard'],
                ['label' => 'student.nav.members', 'icon' => 'users', 'route' => 'student.mess.members.index', 'pattern' => 'student.mess.members.*'],
                ['label' => 'student.nav.meals', 'icon' => 'utensils-crossed', 'route' => 'student.mess.meals.index', 'pattern' => 'student.mess.meals.*'],
                ['label' => 'student.nav.bazar', 'icon' => 'shopping-cart', 'route' => 'student.mess.bazar.index', 'pattern' => 'student.mess.bazar.*'],
                ['label' => 'student.nav.deposits', 'icon' => 'wallet', 'route' => 'student.mess.deposits.index', 'pattern' => 'student.mess.deposits.*'],
                ['label' => 'student.nav.expenses', 'icon' => 'receipt', 'route' => 'student.mess.expenses.index', 'pattern' => 'student.mess.expenses.*'],
                ['label' => 'student.nav.accounts', 'icon' => 'landmark', 'route' => 'student.mess.accounts.index', 'pattern' => 'student.mess.accounts.*', 'ability' => 'manage_finance'],
                ['label' => 'student.nav.transactions', 'icon' => 'arrow-left-right', 'route' => 'student.mess.transactions.index', 'pattern' => 'student.mess.transactions.*', 'ability' => 'manage_finance'],
                ['label' => 'student.nav.settlements', 'icon' => 'calendar-check', 'route' => 'student.mess.settlements.index', 'pattern' => 'student.mess.settlements.*'],
                ['label' => 'student.nav.reports', 'icon' => 'bar-chart-3', 'route' => 'student.mess.reports.index', 'pattern' => 'student.mess.reports.*'],
            ],
            'settings' => [
                ['label' => 'student.nav.mess_settings', 'icon' => 'settings', 'route' => 'student.mess.settings.edit', 'pattern' => 'student.mess.settings.*', 'ownerOnly' => true],
                ['label' => 'student.nav.member_permissions', 'icon' => 'shield-check', 'route' => 'student.mess.permissions.index', 'pattern' => 'student.mess.permissions.*', 'ownerOnly' => true],
                ['label' => 'student.nav.profile', 'icon' => 'user-cog', 'route' => 'student.profile.edit', 'pattern' => 'student.profile.*'],
            ],
        ],
    ];

    public function __construct(private readonly MessService $messes) {}

    /**
     * Build the product-aware navigation for an authenticated account.
     *
     * @return array{products: list<array<string, mixed>>, activeProducts: list<string>, hasActiveProduct: bool}
     */
    public function forUser(?User $user, ?Mess $mess = null): array
    {
        if ($user === null) {
            return ['products' => [], 'activeProducts' => [], 'hasActiveProduct' => false];
        }

        $mess ??= $this->messes->activeMessFor($user, session('student_mess_id'));
        $member = $mess?->memberFor($user);
        $products = [];
        $activeProducts = [];

        foreach ($user->availableProducts() as $product) {
            if (! isset(self::PRODUCTS[$product]) || ! Route::has(self::PRODUCTS[$product]['dashboard'])) {
                continue;
            }

            $definition = self::PRODUCTS[$product];
            $isActive = $user->hasActiveSubscription($product);

            if ($isActive) {
                $activeProducts[] = $product;
            }

            $products[] = [
                'product' => $product,
                'label' => __($definition['label']),
                'navLabel' => __($definition['navLabel']),
                'icon' => $definition['icon'],
                'active' => $isActive,
                'url' => route($definition['dashboard']),
                'activationUrl' => route('subscriptions.show', $product),
                'main' => $isActive
                    ? $this->links($definition['main'], $mess, $member)
                    : [],
                'settings' => $isActive
                    ? $this->links($definition['settings'], $mess, $member)
                    : [],
            ];
        }

        return [
            'products' => $products,
            'activeProducts' => $activeProducts,
            'hasActiveProduct' => $activeProducts !== [],
        ];
    }

    /**
     * Resolve the visible links for one navigation group.
     *
     * @param  list<array<string, mixed>>  $definitions
     * @return list<array{label: string, icon: string, url: string, pattern: string}>
     */
    private function links(array $definitions, ?Mess $mess, ?MessMember $member): array
    {
        $links = [];

        foreach ($definitions as $definition) {
            $route = (string) $definition['route'];

            if (! Route::has($route) || ! $this->isVisible($definition, $member)) {
                continue;
            }

            $requiresMess = str_starts_with($route, 'student.mess.');

            if ($requiresMess && $mess === null && ! Route::has('student.messes.index')) {
                continue;
            }

            $links[] = [
                'label' => __((string) $definition['label']),
                'icon' => (string) $definition['icon'],
                'url' => $requiresMess && $mess === null
                    ? route('student.messes.index')
                    : route($route, $requiresMess ? [$mess] : []),
                'pattern' => (string) $definition['pattern'],
            ];
        }

        return $links;
    }

    /**
     * Check the existing membership abilities that already guard these features.
     *
     * @param  array<string, mixed>  $definition
     */
    private function isVisible(array $definition, ?MessMember $member): bool
    {
        if (isset($definition['ownerOnly']) && $definition['ownerOnly'] === true) {
            return $member?->isOwner() === true;
        }

        $ability = $definition['ability'] ?? null;

        return $ability === null || $member?->allows((string) $ability) === true;
    }
}
