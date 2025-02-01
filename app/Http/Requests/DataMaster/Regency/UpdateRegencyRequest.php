<?php

namespace App\Http\Requests\DataMaster\Regency;

use App\Models\DataMaster\Regency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRegencyRequest extends FormRequest
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
        $regencyId = Regency::where('uuid', $this->route(param: 'uuid'))->value('id')?? null;
        return [
            'province_id' => 'nullable|exists:provinces,uuid',
            'name' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('regencies', 'name')->ignore($regencyId),
            ],
            'alt_name' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ];
    }
}
