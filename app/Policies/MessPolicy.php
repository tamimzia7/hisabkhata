<?php

namespace App\Policies;

use App\Models\Mess;
use App\Models\User;

class MessPolicy
{
    /**
     * Determine whether the user can browse their mess workspaces.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasActiveStudentSubscription();
    }

    /**
     * Determine whether the user can open the mess.
     */
    public function view(User $user, Mess $mess): bool
    {
        return $mess->hasMember($user);
    }

    /**
     * Only the workspace owner may update or delete it.
     */
    public function update(User $user, Mess $mess): bool
    {
        return $mess->owner_id === $user->getKey();
    }

    /**
     * Determine whether the user may delete the workspace.
     */
    public function delete(User $user, Mess $mess): bool
    {
        return $this->update($user, $mess);
    }

    /**
     * Determine whether the user may manage members and invitations.
     */
    public function manageMembers(User $user, Mess $mess): bool
    {
        return $this->roleIn($mess, $user, 'manage_members');
    }

    /**
     * Determine whether the user may manage meals.
     */
    public function manageMeals(User $user, Mess $mess): bool
    {
        return $this->roleIn($mess, $user, 'manage_meals');
    }

    /**
     * Determine whether the user may manage bazar records.
     */
    public function manageBazar(User $user, Mess $mess): bool
    {
        return $this->roleIn($mess, $user, 'manage_bazar');
    }

    /**
     * Determine whether the user may manage deposits, expenses, and accounts.
     */
    public function manageFinance(User $user, Mess $mess): bool
    {
        return $this->roleIn($mess, $user, 'manage_finance');
    }

    /**
     * Determine whether the user may finalize or reopen settlements.
     */
    public function manageSettlements(User $user, Mess $mess): bool
    {
        return $this->roleIn($mess, $user, 'manage_settlements');
    }

    /**
     * Determine whether the user may browse reports.
     */
    public function viewReports(User $user, Mess $mess): bool
    {
        return $this->roleIn($mess, $user, 'view_reports');
    }

    /**
     * Determine whether the user may change member permissions.
     */
    public function managePermissions(User $user, Mess $mess): bool
    {
        return $mess->owner_id === $user->getKey();
    }

    /**
     * Check for an active member allowed to perform the given ability.
     */
    private function roleIn(Mess $mess, User $user, string $ability): bool
    {
        $member = $mess->memberFor($user);

        return $member !== null
            && $member->isActive()
            && $member->allows($ability);
    }
}
