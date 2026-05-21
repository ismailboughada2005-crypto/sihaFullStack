<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreAppointmentRequest extends FormRequest
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
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:medecins,id',
            'appointment_date' => 'required|date',
            'appointment_time' => 'required|string|max:10',
            'type' => 'required|string|max:50',
            'status' => 'required|string|max:20',
            'notes' => 'nullable|string',
            'room_id' => 'nullable|exists:rooms,id'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $patientId = $this->input('patient_id');
            $appointmentDate = $this->input('appointment_date');
            $appointmentTime = $this->input('appointment_time');
            $status = $this->input('status', 'pending');

            // If the appointment status is pending or confirmed, check for conflicts
            if (in_array($status, ['pending', 'confirmed'])) {
                if ($patientId && $appointmentDate && $appointmentTime) {
                    $exists = \App\Models\Appointment::where('patient_id', $patientId)
                        ->where('appointment_date', $appointmentDate)
                        ->where('appointment_time', $appointmentTime)
                        ->whereIn('status', ['pending', 'confirmed'])
                        ->exists();

                    if ($exists) {
                        $validator->errors()->add('appointment_time', 'You already have an appointment at this time.');
                    }
                }

                // Check for room conflict
                $roomId = $this->input('room_id');
                if ($roomId && $appointmentDate && $appointmentTime) {
                    $roomOccupied = \App\Models\Appointment::where('room_id', $roomId)
                        ->where('appointment_date', $appointmentDate)
                        ->where('appointment_time', $appointmentTime)
                        ->whereIn('status', ['pending', 'confirmed'])
                        ->exists();

                    if ($roomOccupied) {
                        $validator->errors()->add('room_id', 'Room is already occupied at this time.');
                    }
                }
            }
        });
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors();
        $firstError = $errors->first();
        
        throw new HttpResponseException(response()->json([
            'message' => $firstError ?: 'The given data was invalid.',
            'errors' => $errors
        ], 422));
    }
}
