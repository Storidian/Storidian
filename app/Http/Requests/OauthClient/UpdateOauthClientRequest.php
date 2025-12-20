<?php

namespace App\Http\Requests\OauthClient;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOauthClientRequest extends FormRequest
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
            'redirect_uris' => ['sometimes', 'array', 'min:1'],
            'redirect_uris.*' => ['required', 'url'],
            'scopes' => ['sometimes', 'array'],
            'scopes.*' => ['required', 'string'],
            'is_first_party' => ['sometimes', 'boolean'],
            'is_public' => ['sometimes', 'boolean'],
        ];
    }
}

