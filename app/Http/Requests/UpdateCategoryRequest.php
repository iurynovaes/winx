<?php

namespace App\Http\Requests;

use App\Domain\Catalog\Rules\UniqueCategoryName;
use App\Models\Category;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
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
        $category = $this->route('category');

        return [
            'nome' => [
                'required',
                'string',
                'max:255',
                new UniqueCategoryName($category instanceof Category ? $category->id : null),
            ],
        ];
    }

    /**
     * Get the validation messages used by the API contract.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nome.required' => 'Informe o nome.',
            'nome.max' => 'O nome deve ter no máximo :max caracteres.',
        ];
    }
}
