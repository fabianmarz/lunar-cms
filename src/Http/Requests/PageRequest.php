<?php

namespace LunarCms\Http\Requests;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use LunarCms\Blocks\BlockRegistry;
use LunarCms\Models\Page;
use LunarCms\PageStatus;
use LunarCms\Support\Languages;

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
     * Derive each language's slug from its title when the editor leaves it
     * blank, and drop empty translations so the stored maps stay normalised.
     */
    protected function prepareForValidation(): void
    {
        $titles = array_filter((array) $this->input('title'), fn ($value) => filled($value));
        $slugs = array_filter((array) $this->input('slug'), fn ($value) => filled($value));

        foreach ($titles as $code => $title) {
            $slugs[$code] ??= Str::slug((string) $title);
        }

        $this->merge(['title' => $titles, 'slug' => array_filter($slugs, fn ($value) => filled($value))]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $default = Languages::defaultCode();

        return [
            'title' => ['required', 'array'],
            "title.{$default}" => ['required', 'string', 'max:255'],
            'title.*' => ['string', 'max:255'],
            'slug' => ['required', 'array'],
            "slug.{$default}" => ['required'],
            'slug.*' => [
                'string',
                'max:255',
                'alpha_dash:ascii',
                fn (string $attribute, mixed $value, Closure $fail) => $this->slugIsTaken($attribute, $value)
                    ? $fail(__('validation.unique', ['attribute' => 'slug']))
                    : null,
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
     * Slugs must be unique per language: the same slug may exist in two
     * languages, but never twice within one.
     */
    private function slugIsTaken(string $attribute, mixed $value): bool
    {
        $code = Str::after($attribute, 'slug.');

        return Page::query()
            ->where("slug->{$code}", $value)
            ->when($this->route('page'), fn ($query, $page) => $query->whereKeyNot($page->getKey()))
            ->exists();
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
