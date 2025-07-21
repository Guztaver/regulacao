<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class StorePatientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Ensure user is authenticated
        if (! Auth::check()) {
            return false;
        }

        // Additional authorization logic can be added here
        // For now, any authenticated user can create patients
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'min:2',
                'regex:/^[a-zA-ZÀ-ÿñÑ\s]+$/u', // Only letters, accents, and spaces
            ],

            'phone' => [
                'nullable',
                'string',
                'max:20',
                'min:10',
                'regex:/^[\d\s\(\)\-\+]+$/', // Numbers, spaces, parentheses, hyphens, plus
            ],
            'sus_number' => [
                'nullable',
                'string',
                'size:15',
                'unique:patients,sus_number',
                'regex:/^[\d\s]+$/', // Only numbers and spaces for SUS number
            ],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome do paciente é obrigatório.',
            'name.min' => 'O nome deve ter pelo menos 2 caracteres.',
            'name.regex' => 'O nome deve conter apenas letras e espaços.',
            'email.required' => 'O email é obrigatório.',
            'email.email' => 'O email deve ter um formato válido.',
            'email.unique' => 'Este email já está cadastrado para outro paciente.',
            'phone.min' => 'O telefone deve ter pelo menos 10 dígitos.',
            'phone.regex' => 'O telefone deve conter apenas números e caracteres de formatação.',
            'sus_number.size' => 'O número do SUS deve ter exatamente 15 caracteres.',
            'sus_number.unique' => 'Este número do SUS já está cadastrado para outro paciente.',
            'sus_number.regex' => 'O número do SUS deve conter apenas números.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Ensure user is still authenticated during validation
            if (! Auth::check()) {
                throw ValidationException::withMessages([
                    'auth' => 'Usuário deve estar autenticado para criar pacientes.',
                ]);
            }

            // Additional custom validation logic can be added here
            $this->validateSusNumber($validator);
        });
    }

    /**
     * Custom validation for SUS number format
     */
    protected function validateSusNumber($validator)
    {
        $susNumber = $this->input('sus_number');

        if ($susNumber) {
            // Remove spaces and format
            $cleanSus = preg_replace('/\s+/', '', $susNumber);

            // SUS number should be exactly 15 digits
            if (strlen($cleanSus) !== 15 || ! ctype_digit($cleanSus)) {
                $validator->errors()->add('sus_number', 'O número do SUS deve ter exatamente 15 dígitos.');
            }
        }
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Clean and format data before validation
        $data = [];

        if ($this->has('name')) {
            $data['name'] = trim($this->input('name'));
        }

        if ($this->has('email')) {
            $data['email'] = strtolower(trim($this->input('email')));
        }

        if ($this->has('phone')) {
            $data['phone'] = trim($this->input('phone'));
        }

        if ($this->has('sus_number')) {
            $data['sus_number'] = trim($this->input('sus_number'));
        }

        $this->merge($data);
    }
}
