<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'patient_id' => 'sometimes|exists:patients,id',
            'doctor_id' => 'sometimes|exists:medecins,id',
            'appointment_date' => 'sometimes|date',
            'appointment_time' => 'sometimes|string|max:10',
            'type' => 'sometimes|string|max:50',
            'status' => 'sometimes|string|max:20',
            'notes' => 'sometimes|nullable|string',
            'cancellation_reason' => 'sometimes|nullable|string'
        ];
    }
}
