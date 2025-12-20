<?php

namespace App\Http\Requests\SystemApiKey;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSystemApiKeyRequest extends FormRequest
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
            'scopes' => ['sometimes', 'array', 'min:1'],
            'scopes.*' => ['required', 'string'],
            'allowed_users' => ['sometimes', 'nullable', 'array'],
            'allowed_users.*' => ['uuid', 'exists:users,id'],
            'expires_at' => ['sometimes', 'nullable', 'date', 'after:now'],
        ];
    }
}

