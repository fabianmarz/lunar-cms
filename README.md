# Lunar CMS add-on

Page builder for the Lunar admin panel: pages with per-locale title/slug and a
block-based content editor. Blocks are **schema-driven** — a PHP declaration
produces the insert-menu entry, the rendered inputs, the defaults and the
validation rules. Most new block types need zero JavaScript.

## Screenshots

| Pages index | Schema-driven block editor | Insert menu |
|---|---|---|
| ![Pages index](docs/screenshots/pages-index.png) | ![Block editor — fields rendered from the PHP schema](docs/screenshots/block-editor.png) | ![Block insert menu](docs/screenshots/block-insert-menu.png) |

## Installation

Requires PHP 8.3+ and `lunarphp/panel` 2.x — this is an add-on for the Lunar 2
admin panel, not a standalone app.

The package isn't on Packagist yet, so point composer at the repo first:

```json
"repositories": [
    { "type": "vcs", "url": "https://github.com/fabianmarz/lunar-cms" }
]
```

```bash
composer require lunar-2-test/cms
php artisan migrate           # creates the pages table
php artisan lunar:panel:link  # symlinks the compiled bundle into public/vendor/lunar-panel/cms
```

`CmsServiceProvider` is auto-discovered — it registers the panel section,
routes under `/panel/cms`, translations and migrations. Nothing else to wire:
the CMS appears under **Content → Pages** in the panel nav, and the frontend
bundle ships prebuilt (no npm step for consumers).

## Development

Inside the monorepo-style app this package is developed in, it is wired via a
`path` composer repository (`packages/cms`, namespace `LunarCms\`):

```bash
cd packages/cms
npm install        # once — deps externalize against panel globals
npm run build      # produces build/ (committed, linked via lunar:panel:link)
npm run dev        # vite build --watch while editing JS
```

PHP formatting after changes: `vendor/bin/pint --dirty --format agent` from the
app root. Tests: `php artisan test --filter=Cms`.

## Architecture

```
BlockRegistry (type => Block class)   PageController          BlockEditor.vue
┌─────────────────────┐   Inertia ┌───────────────┐        ┌──────────────────────────┐
│ 'cta' => CtaBlock   │ ─prop──▶  │ blockTypes    │ ────▶  │ insert menu, labels,     │
│ Block: label, icon  │           │  (schema)     │        │ defaults, card chrome    │
│   fields()/editor() │           └───────────────┘        └──────────┬───────────────┘
└─────────────────────┘                                               │ per block
                                            ┌─────────────────────────┴──────────────┐
                                            │ editor set? → customEditors[name]      │
                                            │ fields set? → BlockFields.vue          │
                                            │   → fieldTypes[field.type] adapter     │
                                            └────────────────────────────────────────┘
```

`PageRequest` derives validation from the same registry
(`blocks.{locale}.{i}.data.{key}`), so a field's rules live exactly once — in
its field class. Stored data shape is `{id, type, enabled, data}` per block,
nested under `pages.blocks` per locale. No data migration is needed when
adding a type.

## Adding a block (pure PHP)

Create a `Block` subclass in `src/Blocks/` and register it:

```php
namespace LunarCms\Blocks;

use LunarCms\Fields\Select;
use LunarCms\Fields\Text;
use LunarCms\Fields\Textarea;

class QuoteBlock extends Block
{
    public function label(): string
    {
        return 'Quote';
    }

    public function icon(): string
    {
        return 'fileText';
    }

    public function fields(): array
    {
        return [
            Textarea::make('text', 'Quote')
                ->required()
                ->cols(2),
            Text::make('cite', 'Citation')
                ->cols(1)
                ->rules(['max:120']),
            Select::make('variant', 'Style')
                ->required()
                ->cols(1)
                ->options([
                    ['label' => 'Default', 'value' => 'default'],
                    ['label' => 'Highlight', 'value' => 'highlight'],
                ]),
        ];
    }
}
```

```php
// CmsServiceProvider::boot(), or any provider in another package
BlockRegistry::register('quote', QuoteBlock::class);
```

That is the whole job: the type appears in the insert menu, renders inputs,
seeds defaults and validates — no Vue changes. `icon` must be one of the names
in the panel `Icon.vue`'s `ICONS` map (closed set — `fileText`, `image`,
`link`, `type`, `flag`, `palette`, …); unknown names render nothing.

### Field builder API

`Field::make(key, label)` starts every field; each method returns the field
for chaining:

| Method       | Effect |
|--------------|--------|
| `required()` | `required` vs `nullable` in derived rules; asterisk in the UI |
| `cols(int)`  | Columns in the 2-column grid: `1` = half width, `2` = full (default) |
| `rules([])`  | Extra Laravel rules merged after the type's base |
| `default($v)`| Initial value for new blocks (falls back to the type's implicit default) |
| `config([])` | Type-specific config shipped to the renderer |
| `options([])`| `Select` only — options list, also feeds `Rule::in` |

### Built-in field types

| Type       | PHP class          | Renders     | Base rule | Notes |
|------------|--------------------|-------------|-----------|-------|
| `text`     | `Fields\Text`      | TextInput   | `string`  | |
| `url`      | `Fields\Url`       | TextInput (`type=url`) | `string` | |
| `number`   | `Fields\Number`    | TextInput (`type=number`) | `numeric` | empty → `null`, else coerced to `Number` |
| `textarea` | `Fields\Textarea`  | Textarea    | `string`  | |
| `select`   | `Fields\Select`    | Select      | `Rule::in(options)` | options via `->options()` |
| `toggle`   | `Fields\Toggle`    | Toggle      | `boolean` | |
| `richtext` | `Fields\RichText`  | TipTap editor | `string` | stores HTML |

Validation is derived as `[required|nullable, <base>, ...custom rules]` — the
`cta` type's `['required', 'string', 'max:255']` is a derived example.

## Custom block editor (escape hatch)

When a block needs behavior a schema can't express (the `image` block needs a
media picker), override `editor()` and keep verbatim `rules()`/`defaults()`:

```php
class ImageBlock extends Block
{
    public function label(): string { return 'Image'; }
    public function icon(): string { return 'image'; }
    public function editor(): ?string { return 'image'; } // name looked up in customEditors

    public function rules(): array { /* ... */ }
    public function defaults(): array { /* ... */ }
}
```

Then register the component in `resources/js/blocks/index.js`:

```js
export const customEditors = {
    image: ImageBlock,
    myblock: MyBlock,
};
```

Editor contract — see `components/blocks/ImageBlock.vue` for a full example:

```ts
defineProps<{
    data: Record<string, unknown>;            // block.data — write through v-model:data
    errors?: Record<string, string>;          // { fieldKey: message }, already scoped to this block
    media: Record<number, MediaRef>;          // resolved media for picker editors
    mediaUploadUrl?: string;
    mediaPickerUrl?: string | null;
    mediaLibraryUploadUrl?: string | null;
}>();

defineEmits<{ 'update:data': [value: ...] }>();
```

Update via `emit('update:data', {...props.data, alt: value})` — `BlockEditor`
binds `v-model:data`.

## Adding a field type

A field type is two halves: a PHP `Field` subclass (`src/Fields/`) that owns
`type()`, base rules and default; and a JS renderer resolved through
`fieldTypes` in `resources/js/components/fields/index.ts` — the JS mirror of
the PHP registry.

1. Create the PHP side:

```php
class Color extends Field
{
    public function type(): string
    {
        return 'color';
    }
    // baseRules() → ['string'] unless the type needs a different default.
}
```

2. Create `components/fields/ColorField.vue` implementing the uniform contract:

1. Create `components/fields/MyField.vue` implementing the uniform contract:

```vue
<script setup lang="ts">
import type { BlockField } from './types';

defineProps<{
    field: BlockField;        // the schema entry: key, type, label, config…
    modelValue?: unknown;     // current data[field.key]
    invalid?: boolean;        // a validation error exists for this key
}>();

const emit = defineEmits<{ 'update:modelValue': [value: string] }>();
</script>

<template>
    <!-- normalize your input's props/events onto the contract -->
</template>
```

3. Register the renderer — either statically in `fieldTypes`:

```ts
export const fieldTypes = shallowReactive<Record<string, Component>>({
    // ...
    color: ColorField,
});
```

or at runtime from another add-on (it is `shallowReactive`, so late
registration re-renders open forms):

```ts
import { registerFieldType } from 'cms/components/fields';
registerFieldType('color', ColorField);
```

4. Use it in any block: `Color::make('accent', 'Accent color')`.

`BlockField` lives in `components/fields/types.ts`; `fieldString()` there is
the shared `unknown → string` coercion for text-like inputs.

## Conventions that will bite you

- **Shared compiled CSS.** Add-ons share the panel's Tailwind build — a utility
  class only exists if the panel emitted it. `cols` therefore renders as inline
  `grid-column`, and genuinely new utilities go in the `<style>` injected by
  `addon.ts`. Check `getComputedStyle` or the injected stylesheet before
  inventing a class.
- **Public surface only.** Import panel pieces from `@lunarphp/panel` (the
  `ui.ts` barrel → `window.LunarPanelUI`). `RichTextEditor` is *not* exported —
  `RichTextField` wraps TipTap directly.
- **Externalized deps.** Vue, Inertia, vue-i18n and `@lunarphp/panel` resolve
  to panel globals — never bundle them. `@tiptap/*` resolves from the app root's
  `node_modules`.
- **Reactivity is direct mutation.** Field adapters emit `update:modelValue`;
  `BlockFields` writes `data[field.key]` on the form's reactive object. No
  per-block emit wiring — keep it that way.
- **Formatting.** Declaration arrays one key per line
  (`.ai/rules/packages.md`) — Pint accepts single-line arrays but they don't
  diff or read well.
