<?php

namespace LunarCms\Fields;

class Toggle extends Field
{
    public function type(): string
    {
        return 'toggle';
    }

    public function baseRules(): array
    {
        return ['boolean'];
    }

    public function fallbackDefault(): mixed
    {
        return false;
    }
}
