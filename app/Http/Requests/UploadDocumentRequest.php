<?php

namespace App\Http\Requests;

use App\Models\MaterialDocument;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UploadDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $material = $this->route('material');

        return $material && $this->user() && $this->user()->can('create', [MaterialDocument::class, $material]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'document' => [
                'required',
                'file',
                'mimes:pdf,docx',
                'extensions:pdf,docx',
                'max:20480', // 20 MB max
            ],
        ];
    }
}
