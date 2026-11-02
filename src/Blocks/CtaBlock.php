<?php

namespace LunarCms\Blocks;

use LunarCms\Fields\Text;

class CtaBlock extends Block
{
    public function label(): string
    {
        return 'Button';
    }

    public function icon(): string
    {
        return 'link';
    }

    public function fields(): array
    {
        return [
            Text::make('label', 'Button label')
                ->required()
                ->cols(1)
                ->rules(['max:255']),
            Text::make('url', 'URL')
                ->required()
                ->cols(1)
                ->rules(['max:2048']),
        ];
    }
}
