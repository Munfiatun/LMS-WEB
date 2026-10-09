<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSlidebookDesignRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $slidebook = $this->route('slidebook');
        return $slidebook && in_array($slidebook->status, ['draft', 'review']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'preset' => ['required', 'string', 'in:indigo-dark,modern-tech,academic-blue,creative-education,fresh-learning,minimalist'],
        ];
    }
}
