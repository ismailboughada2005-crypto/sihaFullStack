<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

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
            'cancellation_reason' => 'sometimes|nullable|string',
            'room_id' => 'sometimes|nullable|exists:rooms,id'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $appointment = $this->route('appointment');
            if (!$appointment) {
                return;
            }
            $appointmentId = $appointment instanceof \App\Models\Appointment ? $appointment->id : $appointment;
            $appointmentInstance = $appointment instanceof \App\Models\Appointment ? $appointment : \App\Models\Appointment::find($appointmentId);
            
            if (!$appointmentInstance) {
                return;
            }

            $patientId = $this->input('patient_id', $appointmentInstance->patient_id);
            $appointmentDate = $this->input('appointment_date', $appointmentInstance->appointment_date);
            $appointmentTime = $this->input('appointment_time', $appointmentInstance->appointment_time);
            $status = $this->input('status', $appointmentInstance->status);

            // If the updated status is pending or confirmed, verify no other conflict exists
            if (in_array($status, ['pending', 'confirmed'])) {
                if ($patientId && $appointmentDate && $appointmentTime) {
                    $exists = \App\Models\Appointment::where('patient_id', $patientId)
                        ->where('appointment_date', $appointmentDate)
                        ->where('appointment_time', $appointmentTime)
                        ->whereIn('status', ['pending', 'confirmed'])
                        ->where('id', '!=', $appointmentId)
                        ->exists();

                    if ($exists) {
                        $validator->errors()->add('appointment_time', 'You already have an appointment at this time.');
                    }
                }

                // Check for room conflict (excluding current appointment)
                $roomId = $this->input('room_id', $appointmentInstance->room_id);
                if ($roomId && $appointmentDate && $appointmentTime) {
                    $roomOccupied = \App\Models\Appointment::where('room_id', $roomId)
                        ->where('appointment_date', $appointmentDate)
                        ->where('appointment_time', $appointmentTime)
                        ->whereIn('status', ['pending', 'confirmed'])
                        ->where('id', '!=', $appointmentId)
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
