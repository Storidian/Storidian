<?php

namespace App\Http\Requests\AuthProvider;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAuthProviderRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('auth_providers', 'slug')],
            'provider_class' => ['required', 'string', 'max:255'],
            'config' => ['required', 'array'],
            'enabled' => ['boolean'],
            'allow_registration' => ['boolean'],
            'trust_email' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ];
    }
}

