<?php

namespace LunarCms\Blocks;

use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The block types editors can place on a page, and the server-side contract
 * for each type's `data` payload. Blocks are stored per language, so a field's
 * value is plain: the language is the block list's key, not the field's. The Vue editor mirrors this registry —
 * a type added here needs a matching editor component in resources/js/blocks.
 */
class BlockRegistry
{
    /**
     * @return array<string, array{label: string, rules: array<string, array<int, string|ValidationRule>>}>
     */
    public static function types(): array
    {
        return [
            'rich_text' => [
                'label' => 'Rich text',
                'rules' => [
                    'content' => ['required', 'string'],
                ],
            ],
            'image' => [
                'label' => 'Image',
                'rules' => [
                    'media_id' => ['required', 'integer', 'exists:media,id'],
                    'alt' => ['nullable', 'string', 'max:255'],
                ],
            ],
            'cta' => [
                'label' => 'Button',
                'rules' => [
                    'label' => ['required', 'string', 'max:255'],
                    'url' => ['required', 'string', 'max:2048'],
                ],
            ],
        ];
    }

    public static function has(string $type): bool
    {
        return array_key_exists($type, self::types());
    }
}
