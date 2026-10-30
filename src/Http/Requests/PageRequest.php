<?php

namespace LunarCms\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use LunarCms\Blocks\BlockRegistry;
use LunarCms\PageStatus;

class PageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('cms:manage-pages') ?? false;
    }

    /**
     * Derive the slug from the title when the editor leaves it blank.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->filled('slug') && $this->filled('title')) {
            $this->merge(['slug' => Str::slug($this->string('title')->value())]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash:ascii',
                Rule::unique('pages', 'slug')->ignore($this->route('page')),
            ],
            'status' => ['sometimes', Rule::enum(PageStatus::class)],
            'published_at' => ['nullable', 'date'],
            'blocks' => ['sometimes', 'array'],
            'blocks.*.id' => ['required', 'string'],
            'blocks.*.type' => ['required', 'string', Rule::in(array_keys(BlockRegistry::types()))],
            'blocks.*.enabled' => ['required', 'boolean'],
            'blocks.*.data' => ['required', 'array'],
            ...$this->blockDataRules(),
        ];
    }

    /**
     * Expand `blocks.N.data.*` rules for every submitted block according to
     * its declared type — unknown types are already rejected above, so an
     * unregistered type yields no data rules of its own.
     *
     * @return array<string, array<int, string|ValidationRule>>
     */
    private function blockDataRules(): array
    {
        $rules = [];

        foreach ((array) $this->input('blocks', []) as $index => $block) {
            $type = $block['type'] ?? null;

            if (is_string($type) && BlockRegistry::has($type)) {
                foreach (BlockRegistry::types()[$type]['rules'] as $field => $fieldRules) {
                    $rules["blocks.{$index}.data.{$field}"] = $fieldRules;
                }
            }
        }

        return $rules;
    }
}
