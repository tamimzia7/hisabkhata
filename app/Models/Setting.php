<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Throwable;

#[Fillable(['key', 'value'])]
class Setting extends Model
{
    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'key';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Read a raw setting value.
     */
    public static function value(string $key, ?string $default = null): ?string
    {
        return static::query()->find($key)?->value ?? $default;
    }

    /**
     * Persist a raw setting value.
     */
    public static function put(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * Read an encrypted secret setting.
     */
    public static function secret(string $key, ?string $default = null): ?string
    {
        $value = static::value($key);

        if ($value === null || $value === '') {
            return $default;
        }

        try {
            return Crypt::decryptString($value);
        } catch (Throwable) {
            // Support credentials that were stored before encryption was enabled.
            return $value;
        }
    }

    /**
     * Persist a setting that must be encrypted at rest.
     */
    public static function putSecret(string $key, ?string $value): void
    {
        static::put($key, filled($value) ? Crypt::encryptString((string) $value) : null);
    }

    /**
     * Determine whether a raw setting exists.
     */
    public static function has(string $key): bool
    {
        return static::query()->whereKey($key)->exists();
    }

    /**
     * Read a boolean setting, falling back to the given default.
     */
    public static function boolean(string $key, bool $default = false): bool
    {
        $value = static::value($key);

        return $value === null ? $default : $value === '1';
    }

    /**
     * Persist a boolean setting.
     */
    public static function putBoolean(string $key, bool $value): void
    {
        static::put($key, $value ? '1' : '0');
    }
}
