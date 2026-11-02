<?php

namespace LunarCms\Blocks;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * The single declaration point for the block types editors can place on a
 * page. Two kinds of type exist:
 *
 * - Schema blocks declare `fields`; the panel renders them through the shared
 *   field renderer and their `data` rules and defaults are derived here.
 * - Custom-editor blocks name an `editor` component (registered in
 *   resources/js/blocks/index.js) and keep hand-written `rules`/`defaults`.
 *
 * Blocks are stored per language, so a field's value is plain: the language is
 * the block list's key, not the field's.
 */
class BlockRegistry
{
    /**
     * @return array<string, array{label: string, icon: string, editor?: string, fields?: array<int, array{key: string, type: string, label: string, required?: bool, cols?: int, config?: array<string, mixed>, default?: mixed, rules?: array<int, string|ValidationRule>}>, rules?: array<string, array<int, string|ValidationRule>>, defaults?: array<string, mixed>}>
     */
    public static function types(): array
    {
        return [
            'rich_text' => [
                'label' => 'Rich text',
                'icon' => 'type',
                'fields' => [
                    ['key' => 'content', 'type' => 'richtext', 'label' => 'Content', 'required' => true, 'cols' => 2],
                ],
            ],
            'image' => [
                'label' => 'Image',
                'icon' => 'image',
                'editor' => 'image',
                'rules' => [
                    'media_id' => ['required', 'integer', 'exists:media,id'],
                    'alt' => ['nullable', 'string', 'max:255'],
                ],
                'defaults' => ['media_id' => null, 'alt' => ''],
            ],
            'cta' => [
                'label' => 'Button',
                'icon' => 'link',
                'fields' => [
                    ['key' => 'label', 'type' => 'text', 'label' => 'Button label', 'required' => true, 'cols' => 1, 'rules' => ['max:255']],
                    ['key' => 'url', 'type' => 'text', 'label' => 'URL', 'required' => true, 'cols' => 1, 'rules' => ['max:2048']],
                ],
            ],
        ];
    }

    public static function has(string $type): bool
    {
        return array_key_exists($type, self::types());
    }

    /**
     * Validation rules for a type's `data` payload, keyed by field. Custom
     * editors keep verbatim rules; schema blocks derive them per field:
     * required/nullable, a type base, then the field's own rules.
     *
     * @return array<string, array<int, string|ValidationRule>>
     */
    public static function rules(string $type): array
    {
        $declaration = self::types()[$type] ?? [];

        if (isset($declaration['rules'])) {
            return $declaration['rules'];
        }

        return collect($declaration['fields'] ?? [])
            ->mapWithKeys(fn (array $field) => [$field['key'] => self::fieldRules($field)])
            ->all();
    }

    /**
     * Initial `data` for a freshly inserted block of this type.
     *
     * @return array<string, mixed>
     */
    public static function defaults(string $type): array
    {
        $declaration = self::types()[$type] ?? [];

        if (isset($declaration['defaults'])) {
            return $declaration['defaults'];
        }

        return collect($declaration['fields'] ?? [])
            ->mapWithKeys(fn (array $field) => [
                $field['key'] => $field['default'] ?? self::defaultFor($field['type'] ?? 'text'),
            ])
            ->all();
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
            ->map(fn (array $declaration, string $type) => [
                'label' => $declaration['label'],
                'icon' => $declaration['icon'],
                'editor' => $declaration['editor'] ?? null,
                'fields' => array_map(
                    fn (array $field) => array_diff_key($field, ['rules' => true]),
                    $declaration['fields'] ?? [],
                ),
                'defaults' => self::defaults($type),
            ])
            ->all();
    }

    /**
     * @return array<int, string|ValidationRule>
     */
    private static function fieldRules(array $field): array
    {
        $base = [($field['required'] ?? false) ? 'required' : 'nullable'];

        $typed = match ($field['type'] ?? 'text') {
            'number' => ['numeric'],
            'toggle' => ['boolean'],
            'select' => ['string', Rule::in(self::optionValues($field))],
            default => ['string'],
        };

        return [...$base, ...$typed, ...array_values($field['rules'] ?? [])];
    }

    /**
     * @return array<int, string>
     */
    private static function optionValues(array $field): array
    {
        return array_map(
            fn (array $option) => (string) ($option['value'] ?? $option['label']),
            $field['config']['options'] ?? [],
        );
    }

    private static function defaultFor(string $type): mixed
    {
        return match ($type) {
            'number' => null,
            'toggle' => false,
            default => '',
        };
    }
}
