<?php

namespace App\Http\Requests\User\Permission;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;

class UpdatePermissionRequest extends FormRequest
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
        $permissionId = Permission::where('name', $this->route('name'))->value('id');
        return [
            'name' => [
                'string',
                Rule::unique('permissions', 'name')->ignore($permissionId),
            ],
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
            'name.string' => 'The permission name must be a string.',
            'name.unique' => 'The permission name has already been taken.',
        ];
    }
}