<?php

namespace LunarCms\Blocks;

use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The single declaration point for the block types editors can place on a
 * page. Each type maps to a `Block` class that owns its label, icon, fields,
 * rules, and defaults — the registry only resolves and exposes them.
 *
 * Other code can add types without touching this map:
 * `BlockRegistry::register('gallery', GalleryBlock::class)` from any service
 * provider's `boot()`.
 *
 * Blocks are stored per language, so a field's value is plain: the language is
 * the block list's key, not the field's.
 */
class BlockRegistry
{
    /**
     * Types registered at runtime, keyed by type name.
     *
     * @var array<string, class-string<Block>>
     */
    private static array $registered = [];

    /**
     * Add a block type. Register from a service provider's `boot()`; later
     * registrations for the same key win.
     *
     * @param  class-string<Block>  $block
     */
    public static function register(string $type, string $block): void
    {
        self::$registered[$type] = $block;
    }

    /**
     * @return array<string, class-string<Block>>
     */
    public static function types(): array
    {
        return [
            'rich_text' => RichTextBlock::class,
            'image' => ImageBlock::class,
            'cta' => CtaBlock::class,
            ...self::$registered,
        ];
    }

    public static function has(string $type): bool
    {
        return array_key_exists($type, self::types());
    }

    /**
     * Validation rules for a type's `data` payload, keyed by field.
     *
     * @return array<string, array<int, string|ValidationRule>>
     */
    public static function rules(string $type): array
    {
        return self::block($type)?->rules() ?? [];
    }

    /**
     * Initial `data` for a freshly inserted block of this type.
     *
     * @return array<string, mixed>
     */
    public static function defaults(string $type): array
    {
        return self::block($type)?->defaults() ?? [];
    }

    /**
     * The editor-facing view of every type: what the insert menu and the field
     * renderer need, without server-side rules.
     *
     * @return array<string, array{label: string, icon: string, editor: ?string, fields: array<int, array<string, mixed>>, defaults: array<string, mixed>}>
     */
    public static function schema(): array
    {
        return collect(self::types())
            ->map(fn (string $class) => (new $class)->schema())
            ->all();
    }

    private static function block(string $type): ?Block
    {
        $class = self::types()[$type] ?? null;

        return $class ? new $class : null;
    }
}
