<?php

namespace App\Http\Requests;

use App\Models\LearningMaterial;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMaterialRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // When updating an existing material
        if ($material = $this->route('material')) {
            return $this->user() && $this->user()->can('update', $material);
        }

        // When creating a new material via section
        $section = $this->route('section');

        return $section && $this->user() && $this->user()->can('create', [LearningMaterial::class, $section]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],

            'description' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'duration_minutes' => ['nullable', 'integer', 'min:0'],
            'status' => ['prohibited'],
        ];
    }
}
