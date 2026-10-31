<?php

namespace LunarCms\Support;

use Illuminate\Support\Collection;
use Lunar\Core\Models\Language;

/**
 * The languages page content can be translated into: Lunar's own language
 * records, with the default language first, as the panel's product form does.
 */
class Languages
{
    /**
     * @return Collection<int, Language>
     */
    public static function all(): Collection
    {
        return Language::query()
            ->orderByDesc('default')
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'default']);
    }

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return static::all()->pluck('code')->all() ?: [static::defaultCode()];
    }

    public static function defaultCode(): string
    {
        return Language::query()->where('default', true)->value('code') ?? app()->getLocale();
    }
}
