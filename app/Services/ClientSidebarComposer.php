<?php

namespace App\Services;

use App\Models\Mess;
use App\Models\User;
use Illuminate\View\View;

class ClientSidebarComposer
{
    public function __construct(private readonly ProductNavigationService $navigation) {}

    /**
     * Bind product-aware navigation data to the shared client and student sidebars.
     */
    public function compose(View $view): void
    {
        $data = $view->getData();
        $user = $data['user'] ?? $data['studentUser'] ?? auth()->user();
        $mess = $data['navMess'] ?? null;

        $view->with('clientNavigation', $this->navigation->forUser(
            $user instanceof User ? $user : null,
            $mess instanceof Mess ? $mess : null,
        ));
    }
}
