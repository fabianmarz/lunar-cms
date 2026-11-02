<?php

namespace LunarCms\Blocks;

/**
 * Image needs a media picker and upload, which a field schema can't express —
 * it renders through the `image` editor registered in
 * resources/js/blocks/index.js and keeps verbatim rules and defaults.
 */
class ImageBlock extends Block
{
    public function label(): string
    {
        return 'Image';
    }

    public function icon(): string
    {
        return 'image';
    }

    public function editor(): ?string
    {
        return 'image';
    }

    public function rules(): array
    {
        return [
            'media_id' => ['required', 'integer', 'exists:media,id'],
            'alt' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function defaults(): array
    {
        return ['media_id' => null, 'alt' => ''];
    }
}
