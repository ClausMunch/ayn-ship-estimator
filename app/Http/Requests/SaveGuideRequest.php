<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SaveGuideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $slug = $this->input('slug') ?: $this->input('title');
        if (is_string($slug)) {
            $this->merge(['slug' => Str::slug($slug)]);
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'slug' => ['required', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('guides')->ignore($this->route('guide'))],
            'excerpt' => ['required', 'string', 'max:500'],
            'category' => ['required', 'string', 'max:50'],
            'body' => ['required', 'string', 'max:200000'],
            'author' => ['required', 'string', 'max:100'],
            'status' => ['required', Rule::in(['draft', 'published'])],
        ];
    }
}
