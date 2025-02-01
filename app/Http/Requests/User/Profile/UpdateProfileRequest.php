<?php

namespace App\Http\Requests\User\Profile;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
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
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|string|email|max:255',
            'password' => 'nullable|string|min:6|confirmed',
            'full_name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
            'phone_number' => 'nullable|string|max:15',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|string|in:male,female,other',
            'status_account' => 'nullable|string|max:255',
        ];
    }
}
