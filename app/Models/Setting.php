<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

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
     * Read a boolean setting, falling back to the given default.
     */
    public static function boolean(string $key, bool $default = false): bool
    {
        $value = static::query()->find($key)?->value;

        return $value === null ? $default : $value === '1';
    }

    /**
     * Persist a boolean setting.
     */
    public static function putBoolean(string $key, bool $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value ? '1' : '0']);
    }
}
