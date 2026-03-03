<?php
namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->guest();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required'],
            'email' => ['required', 'email:dns', Rule::unique('sellers')],
            'password' => ['required', 'confirmed'],
        ];
    }
}