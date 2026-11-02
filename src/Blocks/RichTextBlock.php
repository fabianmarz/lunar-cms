<?php

namespace LunarCms\Blocks;

use LunarCms\Fields\RichText;

class RichTextBlock extends Block
{
    public function label(): string
    {
        return 'Rich text';
    }

    public function icon(): string
    {
        return 'type';
    }

    public function fields(): array
    {
        return [
            RichText::make('content', 'Content')
                ->required()
                ->cols(2),
        ];
    }
}
