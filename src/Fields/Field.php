<?php

namespace LunarCms\Fields;

use Illuminate\Contracts\Validation\ValidationRule;

/**
 * One field inside a schema block's `data` payload. Field classes are fluent
 * builders — `Text::make('label', 'Button label')->required()->cols(1)` —
 * that carry everything the panel and the validator need: the rendered type
 * (`type()`), the derived validation rules, and the schema entry the
 * `blockTypes` prop ships to the form.
 */
abstract class Field
{
    protected bool $required = false;

    protected int $cols = 2;

    /**
     * @var array<string, mixed>
     */
    protected array $config = [];

    /**
     * @var array<int, string|ValidationRule>
     */
    protected array $extraRules = [];

    protected mixed $default = null;

    protected bool $hasDefault = false;

    public function __construct(
        protected string $key,
        protected string $label,
    ) {}

    public static function make(string $key, string $label): static
    {
        return new static($key, $label);
    }

    public function required(bool $required = true): static
    {
        $this->required = $required;

        return $this;
    }

    public function cols(int $cols): static
    {
        $this->cols = $cols;

        return $this;
    }

    /**
     * Rules appended after the type's base rules.
     *
     * @param  array<int, string|ValidationRule>  $rules
     */
    public function rules(array $rules): static
    {
        $this->extraRules = $rules;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function config(array $config): static
    {
        $this->config = $config;

        return $this;
    }

    public function default(mixed $default): static
    {
        $this->default = $default;
        $this->hasDefault = true;

        return $this;
    }

    /**
     * The `type` string the JS field renderer resolves.
     */
    abstract public function type(): string;

    /**
     * Rules between required/nullable and the field's own `rules()`.
     *
     * @return array<int, string|ValidationRule>
     */
    public function baseRules(): array
    {
        return ['string'];
    }

    /**
     * Value new blocks start with when no explicit `default()` is set.
     */
    public function fallbackDefault(): mixed
    {
        return '';
    }

    public function key(): string
    {
        return $this->key;
    }

    /**
     * @return array<int, string|ValidationRule>
     */
    public function validationRules(): array
    {
        return [
            $this->required ? 'required' : 'nullable',
            ...$this->baseRules(),
            ...array_values($this->extraRules),
        ];
    }

    public function defaultValue(): mixed
    {
        return $this->hasDefault ? $this->default : $this->fallbackDefault();
    }

    /**
     * Editor-facing schema entry — rules never leave the server.
     *
     * @return array<string, mixed>
     */
    public function toSchema(): array
    {
        $schema = [
            'key' => $this->key,
            'type' => $this->type(),
            'label' => $this->label,
            'required' => $this->required,
            'cols' => $this->cols,
        ];

        if ($this->config !== []) {
            $schema['config'] = $this->config;
        }

        if ($this->hasDefault) {
            $schema['default'] = $this->default;
        }

        return $schema;
    }
}
