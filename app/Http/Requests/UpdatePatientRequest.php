<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePatientRequest extends FormRequest
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
            'nom' => 'sometimes|string|max:50',
            'prenom' => 'sometimes|string|max:50',
            'dob' => 'sometimes|date',
            'gender' => 'sometimes|string|max:10',
            'dept' => 'sometimes|string|max:50',
            'status' => 'sometimes|string|max:20',
            'phone' => 'sometimes|string|max:20',
            'email' => 'sometimes|email|max:50'
        ];
    }
}
