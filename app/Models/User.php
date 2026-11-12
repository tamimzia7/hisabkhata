<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'email', 'phone', 'bio', 'avatar_path', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_SUPER_ADMIN = 'super-admin';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_DEVELOPER = 'developer';

    public const ROLE_CUSTOMER_CARE = 'customer-care';

    public const ROLE_STUDENT = 'student';

    public const ROLE_FAMILY = 'family';

    public const ROLE_SHOP = 'shop';

    /**
     * Roles that may sign in to the admin panel.
     *
     * @var list<string>
     */
    public const SYSTEM_ROLES = [
        self::ROLE_SUPER_ADMIN,
        self::ROLE_ADMIN,
        self::ROLE_DEVELOPER,
        self::ROLE_CUSTOMER_CARE,
    ];

    /**
     * Roles used by client accounts.
     *
     * @var list<string>
     */
    public const CLIENT_ROLES = [
        self::ROLE_STUDENT,
        self::ROLE_FAMILY,
        self::ROLE_SHOP,
    ];

    /**
     * Every role that exists in the application.
     *
     * @var list<string>
     */
    public const ROLES = [
        self::ROLE_SUPER_ADMIN,
        self::ROLE_ADMIN,
        self::ROLE_DEVELOPER,
        self::ROLE_CUSTOMER_CARE,
        self::ROLE_STUDENT,
        self::ROLE_FAMILY,
        self::ROLE_SHOP,
    ];

    /**
     * Every permission that can be toggled for a system role.
     *
     * @var list<string>
     */
    public const PERMISSIONS = ['create_staff', 'remove_staff'];

    /**
     * Determine whether the user is a client account.
     */
    public function isClient(): bool
    {
        return in_array($this->role, self::CLIENT_ROLES, true);
    }

    /**
     * Determine whether the user is a system (admin panel) user.
     */
    public function isSystemUser(): bool
    {
        return in_array($this->role, self::SYSTEM_ROLES, true);
    }

    /**
     * Determine whether the user is an admin panel user.
     */
    public function isAdmin(): bool
    {
        return $this->isSystemUser();
    }

    /**
     * The public URL of the user's avatar, if one has been uploaded.
     */
    public function avatarUrl(): ?string
    {
        return filled($this->avatar_path)
            ? Storage::disk('public')->url($this->avatar_path)
            : null;
    }

    /**
     * Determine whether the user may create or remove system users.
     */
    public function canManageSystemUsers(): bool
    {
        return $this->canCreateSystemUsers() || $this->canRemoveSystemUsers();
    }

    /**
     * Determine whether the user may create system users.
     */
    public function canCreateSystemUsers(): bool
    {
        return $this->hasPermission('create_staff');
    }

    /**
     * Determine whether the user may remove system users.
     */
    public function canRemoveSystemUsers(): bool
    {
        return $this->hasPermission('remove_staff');
    }

    /**
     * Determine whether the user's role holds the given permission.
     */
    public function hasPermission(string $permission): bool
    {
        $role = (string) $this->role;

        return in_array($permission, self::PERMISSIONS, true)
            && $this->assignableStaffRoles() !== []
            && Setting::boolean(self::permissionKey($role, $permission), self::defaultPermission($role, $permission));
    }

    /**
     * The permission state for every system role.
     *
     * @return array<string, array<string, bool>>
     */
    public static function permissionMatrix(): array
    {
        $matrix = [];

        foreach (self::SYSTEM_ROLES as $role) {
            foreach (self::PERMISSIONS as $permission) {
                $matrix[$role][$permission] = Setting::boolean(
                    self::permissionKey($role, $permission),
                    self::defaultPermission($role, $permission)
                );
            }
        }

        return $matrix;
    }

    /**
     * The setting key that stores a role permission.
     */
    public static function permissionKey(string $role, string $permission): string
    {
        return 'permission.'.$role.'.'.$permission;
    }

    /**
     * The default state of a permission before a super admin changes it.
     */
    private static function defaultPermission(string $role, string $permission): bool
    {
        return in_array($permission, self::PERMISSIONS, true)
            && in_array($role, [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN, self::ROLE_DEVELOPER], true);
    }

    /**
     * The system user roles this user is allowed to assign.
     *
     * @return list<string>
     */
    public function assignableStaffRoles(): array
    {
        if (! $this->isSystemUser()) {
            return [];
        }

        return match ($this->role) {
            self::ROLE_SUPER_ADMIN => self::SYSTEM_ROLES,
            self::ROLE_ADMIN => [self::ROLE_DEVELOPER, self::ROLE_CUSTOMER_CARE],
            self::ROLE_DEVELOPER => [self::ROLE_CUSTOMER_CARE],
            default => [],
        };
    }

    /**
     * The subscription records of the user.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    /**
     * The permanent trial history of the user.
     */
    public function subscriptionTrials(): HasMany
    {
        return $this->hasMany(SubscriptionTrial::class);
    }

    /**
     * The payments made by the user.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * The permanent trial record for a product, if one exists.
     */
    public function subscriptionTrial(string $product): ?SubscriptionTrial
    {
        return $this->subscriptionTrials()->where('product', $product)->first();
    }

    /**
     * Determine whether the user has already used the free trial for a product.
     */
    public function hasUsedTrial(string $product): bool
    {
        return $this->subscriptionTrials()->where('product', $product)->exists();
    }

    /**
     * Determine whether the user is still eligible for a product's free trial.
     */
    public function isEligibleForTrial(string $product): bool
    {
        return SubscriptionPlan::isValidProduct($product)
            && ! $this->hasUsedTrial($product)
            && ! $this->hasActiveSubscription($product);
    }

    /**
     * Mess memberships of the user.
     */
    public function messMemberships(): HasMany
    {
        return $this->hasMany(MessMember::class);
    }

    /**
     * Messes owned by the user.
     */
    public function ownedMesses(): HasMany
    {
        return $this->hasMany(Mess::class, 'owner_id');
    }

    /**
     * The latest active subscription for the given product.
     */
    public function activeSubscription(string $product = SubscriptionPlan::PRODUCT_STUDENT): ?UserSubscription
    {
        return $this->subscriptions()
            ->active()
            ->whereHas('plan', fn ($query) => $query->where('product', $product))
            ->latest('ends_at')
            ->first();
    }

    /**
     * Determine whether the user has an active subscription for a product.
     */
    public function hasActiveSubscription(string $product = SubscriptionPlan::PRODUCT_STUDENT): bool
    {
        return $this->activeSubscription($product) !== null;
    }

    /**
     * Determine whether the user has active access to any product.
     */
    public function hasAnyActiveSubscription(): bool
    {
        return $this->subscriptions()
            ->active()
            ->whereHas('plan', fn (Builder $query): Builder => $query->whereIn('product', SubscriptionPlan::PRODUCTS))
            ->exists();
    }

    /**
     * Determine whether the user has an active Student/Mess subscription.
     */
    public function hasActiveStudentSubscription(): bool
    {
        return $this->hasActiveSubscription(SubscriptionPlan::PRODUCT_STUDENT);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
