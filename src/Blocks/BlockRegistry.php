<?php

namespace LunarCms\Blocks;

use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The block types editors can place on a page, and the server-side contract
 * for each type's `data` payload. Translatable fields (rich text content, image
 * alt text, button label) are `{language code: value}` maps. The Vue editor mirrors this registry —
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
                    'content' => ['required', 'array'],
                    'content.*' => ['nullable', 'string'],
                ],
            ],
            'image' => [
                'label' => 'Image',
                'rules' => [
                    'media_id' => ['required', 'integer', 'exists:media,id'],
                    'alt' => ['nullable', 'array'],
                    'alt.*' => ['nullable', 'string', 'max:255'],
                ],
            ],
            'cta' => [
                'label' => 'Button',
                'rules' => [
                    'label' => ['required', 'array'],
                    'label.*' => ['nullable', 'string', 'max:255'],
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
