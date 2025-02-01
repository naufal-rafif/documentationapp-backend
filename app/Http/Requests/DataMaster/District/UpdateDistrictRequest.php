<?php

namespace App\Http\Requests\DataMaster\District;

use App\Models\DataMaster\District;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDistrictRequest extends FormRequest
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
        $regencyId = District::where('uuid', $this->route(param: 'uuid'))->value('id') ?? null;
        return [
            'regency_id' => 'nullable|exists:regencies,uuid',
            'name' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('regencies', 'name')->ignore($regencyId),
            ],
            'alt_name' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ];
    }
}
