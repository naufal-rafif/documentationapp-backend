<?php

namespace App\Http\Requests\User\Permission;

use Illuminate\Foundation\Http\FormRequest;

class AddPermissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|unique:permissions,name',
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The permission name is required.',
            'name.string' => 'The permission name must be a string.',
            'name.unique' => 'The permission name has already been taken.',
        ];
    }
}
