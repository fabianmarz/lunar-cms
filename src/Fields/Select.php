<?php

namespace LunarCms\Fields;

use Illuminate\Validation\Rule;

class Select extends Field
{
    /**
     * @param  array<int, array{label: string, value: string|int}>  $options
     */
    public function options(array $options): static
    {
        $this->config['options'] = $options;

        return $this;
    }

    public function type(): string
    {
        return 'select';
    }

    public function baseRules(): array
    {
        return ['string', Rule::in($this->optionValues())];
    }

    /**
     * @return array<int, string>
     */
    protected function optionValues(): array
    {
        return array_map(
            fn (array $option) => (string) ($option['value'] ?? $option['label']),
            $this->config['options'] ?? [],
        );
    }
}
