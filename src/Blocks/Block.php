<?php

namespace LunarCms\Blocks;

use Illuminate\Contracts\Validation\ValidationRule;
use LunarCms\Fields\Field;

/**
 * One block type editors can place on a page. Two kinds exist:
 *
 * - Schema blocks declare `fields()`; the panel renders them through the
 *   shared field renderer and `rules()`/`defaults()` derive from the fields.
 * - Custom-editor blocks name an `editor()` component (registered in
 *   resources/js/blocks/index.js) and override `rules()`/`defaults()`.
 *
 * A block is portable: register it in any app via
 * `BlockRegistry::register('type', MyBlock::class)`.
 */
abstract class Block
{
    /**
     * Label shown in the insert menu and on the block card.
     */
    abstract public function label(): string;

    /**
     * One of the names in the panel Icon.vue ICONS map.
     */
    abstract public function icon(): string;

    /**
     * Schema-declared fields for this block's `data` payload.
     *
     * @return array<int, Field>
     */
    public function fields(): array
    {
        return [];
    }

    /**
     * Name of a JS-registered custom editor component, when the block needs
     * behavior a field schema can't express.
     */
    public function editor(): ?string
    {
        return null;
    }

    /**
     * Validation rules for the `data` payload, keyed by field.
     *
     * @return array<string, array<int, string|ValidationRule>>
     */
    public function rules(): array
    {
        return collect($this->fields())
            ->mapWithKeys(fn (Field $field) => [$field->key() => $field->validationRules()])
            ->all();
    }

    /**
     * Initial `data` for a freshly inserted block.
     *
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return collect($this->fields())
            ->mapWithKeys(fn (Field $field) => [$field->key() => $field->defaultValue()])
            ->all();
    }

    /**
     * The editor-facing view: what the insert menu and the field renderer
     * need, without server-side rules.
     *
     * @return array{label: string, icon: string, editor: ?string, fields: array<int, array<string, mixed>>, defaults: array<string, mixed>}
     */
    public function schema(): array
    {
        return [
            'label' => $this->label(),
            'icon' => $this->icon(),
            'editor' => $this->editor(),
            'fields' => array_map(fn (Field $field) => $field->toSchema(), $this->fields()),
            'defaults' => $this->defaults(),
        ];
    }
}
