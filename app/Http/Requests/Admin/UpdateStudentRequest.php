<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('student'));
    }

    public function rules(): array
    {
        $student = $this->route('student');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($student->user_id)],
            // Password only changes if the admin actually typed a new one.
            'password' => ['nullable', 'string', 'min:8'],

            'admission_number' => ['required', 'string', 'max:50', Rule::unique('students', 'admission_number')->ignore($student->id)],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'in:male,female,other'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            'class_id' => ['nullable', 'exists:school_classes,id'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'admission_date' => ['required', 'date'],
            'profile_photo' => ['nullable', 'image', 'max:2048'],
            // Enrollment lifecycle status (students.status) — distinct
            // from the User account's active/inactive flag, which has its
            // own dedicated toggle action and is never set from this form.
            'status' => ['required', 'in:active,inactive,graduated,withdrawn'],
        ];
    }
}
