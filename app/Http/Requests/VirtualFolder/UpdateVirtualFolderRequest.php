<?php

namespace App\Http\Requests\VirtualFolder;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVirtualFolderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'tag_query' => ['sometimes', 'array'],
            'tag_query.include' => ['sometimes', 'array'],
            'tag_query.include.*' => ['string'],
            'tag_query.exclude' => ['sometimes', 'array'],
            'tag_query.exclude.*' => ['string'],
            'tag_query.operator' => ['sometimes', 'string', 'in:AND,OR'],
            'sort_order' => ['sometimes', 'string', 'max:255'],
        ];
    }
}

