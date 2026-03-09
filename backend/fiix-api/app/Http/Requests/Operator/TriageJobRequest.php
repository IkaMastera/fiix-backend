<?php

namespace App\Http\Requests\Operator;

use Illuminate\Foundation\Http\FormRequest;

class TriageJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // role check handled in controller/service
    }

    public function rules(): array
    {
        return [
            // Operator can correct the service during triage
            'service_id'        => ['nullable', 'uuid', 'exists:services,id'],

            // Operator can adjust urgency with a reason
            'urgency'           => ['nullable', 'in:low,normal,high,emergency'],

            // Internal notes never shown to customer
            'operator_notes'    => ['nullable', 'string', 'max:1000'],

            // Operator can correct location during triage
            'address_text'      => ['nullable', 'string', 'min:5'],
            'lat'               => ['nullable', 'numeric', 'between:-90,90'],
            'lng'               => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}