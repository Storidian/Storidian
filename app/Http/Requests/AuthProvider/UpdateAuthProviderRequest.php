<?php

namespace App\Http\Requests\AuthProvider;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAuthProviderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
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
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('auth_providers', 'slug')->ignore($this->route('auth_provider')),
            ],
            'provider_class' => ['sometimes', 'string', 'max:255'],
            'config' => ['sometimes', 'array'],
            'enabled' => ['sometimes', 'boolean'],
            'allow_registration' => ['sometimes', 'boolean'],
            'trust_email' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}

