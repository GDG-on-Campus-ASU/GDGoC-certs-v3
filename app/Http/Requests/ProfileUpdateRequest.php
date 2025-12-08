<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
        ];

        // org_name is required only if it's currently null (first-time set)
        // If already set, it's optional in updates
        if ($this->user()->org_name === null) {
            $rules['org_name'] = ['required', 'string', 'max:255'];
        } else {
            $rules['org_name'] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }
}
