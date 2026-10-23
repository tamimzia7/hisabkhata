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
        return $this->isAdmin()
            && in_array($this->role, [self::ROLE_DEVELOPER, self::ROLE_SUB_ADMIN], true);
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
