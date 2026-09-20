<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateParentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('parent'));
    }

    public function rules(): array
    {
        $parentGuardian = $this->route('parent');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($parentGuardian->user_id)],
            'password' => ['nullable', 'string', 'min:8'],

            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],

            'links' => ['nullable', 'array'],
            'links.*.student_id' => ['required', 'exists:students,id'],
            'links.*.relationship' => ['nullable', 'string', 'max:50'],
            'links.*.is_primary' => ['nullable', 'boolean'],
        ];
    }
}
