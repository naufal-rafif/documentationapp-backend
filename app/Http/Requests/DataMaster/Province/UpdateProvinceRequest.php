<?php

namespace App\Http\Requests\DataMaster\Province;

use App\Models\DataMaster\Province;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProvinceRequest extends FormRequest
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
        $provinceId = Province::where('uuid', $this->route(param: 'uuid'))->value('id')?? null;
        return [
            'name' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('provinces', 'name')->ignore($provinceId),
            ],
            'alt_name' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ];
    }
}
