<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePatientRequest extends FormRequest
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
            'nom' => 'required|string|max:50',
            'prenom' => 'required|string|max:50',
            'dob' => 'required|date',
            'gender' => 'required|string|max:10',
            'dept' => 'required|string|max:50',
            'status' => 'required|string|max:20',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:50'
        ];
    }
}
