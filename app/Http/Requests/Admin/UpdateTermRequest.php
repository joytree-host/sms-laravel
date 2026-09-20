<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTermRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('term'));
    }

    public function rules(): array
    {
        $term = $this->route('term');

        return [
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('terms', 'name')
                    ->where(fn ($q) => $q->where('academic_year_id', $this->input('academic_year_id')))
                    ->ignore($term->id),
            ],
            'sequence' => ['required', 'integer', 'min:1', 'max:3'],
            'is_current' => ['nullable', 'boolean'],
        ];
    }
}
