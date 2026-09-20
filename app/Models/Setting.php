<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Generic key/value store. Known settings get a typed static accessor
 * here (see rankingEnabled()) so callers never touch the raw string
 * ->value directly.
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public const RANKING_ENABLED = 'ranking_enabled';

    public static function get(string $key, ?string $default = null): ?string
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    public static function set(string $key, string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /** Whether student/parent results (and later, report cards) should show position/ranking. Defaults to enabled, matching Phase 3's original always-on behavior, until an admin turns it off. */
    public static function rankingEnabled(): bool
    {
        return static::get(self::RANKING_ENABLED, '1') === '1';
    }

    public static function setRankingEnabled(bool $enabled): void
    {
        static::set(self::RANKING_ENABLED, $enabled ? '1' : '0');
    }
}
