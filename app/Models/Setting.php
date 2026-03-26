<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Simple key-value settings store backed by the `settings` table.
 *
 * Usage:
 *   Setting::get('booking_mode', 'form');
 *   Setting::set('booking_mode', 'external');
 */
class Setting extends Model
{
    protected $primaryKey = 'key';

    /** String primary key, not auto-incrementing. */
    public $incrementing = false;

    protected $keyType = 'string';

    /** No created_at / updated_at columns. */
    public $timestamps = false;

    protected $fillable = ['key', 'value'];

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Retrieve the value for the given key, or $default if not found.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return static::find($key)?->value ?? $default;
    }

    /**
     * Persist (insert or update) a setting value.
     */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
