<?php

namespace App\Http\Requests\Operator;

use Illuminate\Foundation\Http\FormRequest;

class AssignJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // The technician to assign this job to
            'technician_id' => ['required', 'uuid', 'exists:users,id'],
        ];
    }
}