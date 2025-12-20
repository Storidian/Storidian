<?php

namespace App\Http\Requests\UserApiKey;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserApiKeyRequest extends FormRequest
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
            'scopes' => ['sometimes', 'array', 'min:1'],
            'scopes.*' => ['required', 'string'],
            'folder_scope' => [
                'sometimes',
                'nullable',
                'uuid',
                Rule::exists('folders', 'id')->where('user_id', $this->user()->id),
            ],
            'expires_at' => ['sometimes', 'nullable', 'date', 'after:now'],
        ];
    }
}

