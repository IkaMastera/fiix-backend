<?php

namespace App\Http\Requests\Operator;

use Illuminate\Foundation\Http\FormRequest;

class ReassignJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // The new technician to assign this job to
            'technician_id' => ['required', 'uuid', 'exists:users,id'],

            // Optional reason for reassignment (goes into audit trail)
            'reason_code'   => ['nullable', 'string', 'max:100'],
            'reason_note'   => ['nullable', 'string', 'max:1000'],
        ];
    }
}