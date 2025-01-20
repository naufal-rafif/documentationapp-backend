<?php

namespace App\Http\Requests\User\Role;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UpdateRoleRequest extends FormRequest
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
        $roleId = Role::where('uuid', $this->route(param: 'uuid'))->value('id');
        return [
            'name' => [
                'string',
                Rule::unique('roles', 'name')->ignore($roleId),
            ],
            'permissions' => 'array'
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
            'name.string' => 'The role name must be a string.',
            'name.unique' => 'The role name has already been taken.',
            'permissions.array' => 'The permissions must be an array.'
        ];
    }
}