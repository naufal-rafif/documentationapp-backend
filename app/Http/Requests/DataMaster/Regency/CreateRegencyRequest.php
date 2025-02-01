<?php

namespace App\Http\Requests\DataMaster\Regency;

use Illuminate\Foundation\Http\FormRequest;

class CreateRegencyRequest extends FormRequest
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
            'province_id' => 'required|exists:provinces,uuid',
            'name' => 'required|string|max:255|unique:regencies,name',
            'alt_name' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ];
    }
}
