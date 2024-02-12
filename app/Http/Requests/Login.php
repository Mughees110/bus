<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class Login extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'password' => 'required|string|max:255',
            'email' => 'required|email|max:255|',
            // Add more rules as needed
        ];
    }
    protected function prepareForValidation()
    {
        // Get the JSON content
        $jsonData = json_decode($this->getContent(), true);

        if ($jsonData === null) {
            // JSON data is null, add a validation error
            return response()->json([
            'message' => 'The given data was invalid.',
            
        ], 422);
        } else {
            // Merge the JSON data if it is not null
            $this->merge($jsonData);
        }
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new \Illuminate\Validation\ValidationException($validator, $this->jsonResponse($validator));
    }

    /**
     * Define the structure of the JSON response for validation errors.
     */
    protected function jsonResponse(\Illuminate\Contracts\Validation\Validator $validator)
    {
        return response()->json([
            'message' => 'The given data was invalid.',
            'errors' => $validator->errors(),
        ], 422);
    }
}
