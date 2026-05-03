<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMedecinRequest extends FormRequest
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
            'specialite' => 'sometimes|string|max:50',
            'email' => 'sometimes|max:50|email',
            'motDePasse' => 'sometimes|min:4|max:100',
            'status' => 'sometimes|string|max:20'
        ];
    }
}
