<?php

namespace App\Http\Requests\Technician;

use Illuminate\Foundation\Http\FormRequest;

class BlockJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Technician must explain why job is blocked
            'reason_code' => ['required', 'string', 'max:100'],
            'reason_note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}