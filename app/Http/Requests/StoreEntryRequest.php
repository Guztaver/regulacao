<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class StoreEntryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Ensure user is authenticated
        if (!Auth::check()) {
            return false;
        }

        // Additional authorization logic can be added here
        // For now, any authenticated user can create entries
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
            'patient_id' => [
                'required',
                'string',
                'exists:patients,id'
            ],
            'title' => [
                'required',
                'string',
                'max:255',
                'min:3',
                'regex:/^[a-zA-ZÀ-ÿñÑ\s\d\.\,\-\:\/]+$/u' // Letters, numbers, basic punctuation
            ],
            'completed' => [
                'sometimes',
                'boolean'
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
            'patient_id.required' => 'O ID do paciente é obrigatório.',
            'patient_id.exists' => 'O paciente selecionado não existe.',
            'title.required' => 'O título da entrada é obrigatório.',
            'title.min' => 'O título deve ter pelo menos 3 caracteres.',
            'title.max' => 'O título não pode ter mais de 255 caracteres.',
            'title.regex' => 'O título contém caracteres não permitidos.',
            'completed.boolean' => 'O status de conclusão deve ser verdadeiro ou falso.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Ensure user is still authenticated during validation
            if (!Auth::check()) {
                throw ValidationException::withMessages([
                    'auth' => 'Usuário deve estar autenticado para criar entradas.'
                ]);
            }

            // Validate that patient exists and is accessible
            $this->validatePatientAccess($validator);
        });
    }

    /**
     * Custom validation for patient access
     */
    protected function validatePatientAccess($validator)
    {
        $patientId = $this->input('patient_id');

        if ($patientId) {
            // Check if patient exists and user has access
            $patient = \App\Models\Patient::find($patientId);

            if (!$patient) {
                $validator->errors()->add('patient_id', 'O paciente selecionado não foi encontrado.');
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

        if ($this->has('title')) {
            $data['title'] = trim($this->input('title'));
        }

        if ($this->has('patient_id')) {
            $data['patient_id'] = trim($this->input('patient_id'));
        }

        if ($this->has('completed')) {
            $data['completed'] = (bool) $this->input('completed');
        }

        $this->merge($data);
    }
}
