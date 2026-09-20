<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSchoolClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('class'));
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'section' => ['nullable', 'string', 'max:50'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'status' => ['required', 'in:active,inactive'],
        ];
    }
}
