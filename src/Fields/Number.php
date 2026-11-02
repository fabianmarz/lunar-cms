<?php

namespace LunarCms\Fields;

class Number extends Field
{
    public function type(): string
    {
        return 'number';
    }

    public function baseRules(): array
    {
        return ['numeric'];
    }

    public function fallbackDefault(): mixed
    {
        return null;
    }
}
