<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'mode', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_DEVELOPER = 'developer';

    public const ROLE_SUB_ADMIN = 'sub-admin';

    public const ROLE_CUSTOMER_CARE = 'customer-care';

    /**
     * Every role that exists in the admin panel.
     *
     * @var list<string>
     */
    public const ROLES = [self::ROLE_DEVELOPER, self::ROLE_SUB_ADMIN, self::ROLE_CUSTOMER_CARE];

    /**
     * Every permission that can be toggled for a role.
     *
     * @var list<string>
     */
    public const PERMISSIONS = ['create_staff', 'remove_staff'];

    /**
     * Determine whether the user is an admin panel user.
     */
    public function isAdmin(): bool
    {
        return $this->mode === 'admin';
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
     * The permission state for every role.
     *
     * @return array<string, array<string, bool>>
     */
    public static function permissionMatrix(): array
    {
        $matrix = [];

        foreach (self::ROLES as $role) {
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
     * The default state of a permission before an admin changes it.
     */
    private static function defaultPermission(string $role, string $permission): bool
    {
        return in_array($permission, self::PERMISSIONS, true)
            && in_array($role, [self::ROLE_DEVELOPER, self::ROLE_SUB_ADMIN], true);
    }

    /**
     * The system user roles this admin is allowed to assign.
     *
     * @return list<string>
     */
    public function assignableStaffRoles(): array
    {
        if (! $this->isAdmin()) {
            return [];
        }

        return match ($this->role) {
            self::ROLE_DEVELOPER => [self::ROLE_DEVELOPER, self::ROLE_SUB_ADMIN, self::ROLE_CUSTOMER_CARE],
            self::ROLE_SUB_ADMIN => [self::ROLE_CUSTOMER_CARE],
            default => [],
        };
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
