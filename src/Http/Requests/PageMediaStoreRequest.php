<?php

namespace LunarCms\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PageMediaStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('cms:manage-pages') ?? false;
    }

    /**
     * Same ceiling and image-only constraint the panel applies to product media.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'image',
                'max:'.config('lunar.media.max_upload_kb', 8192),
            ],
        ];
    }
}
