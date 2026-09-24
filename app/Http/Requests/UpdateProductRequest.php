<?php

namespace App\Http\Requests;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize the SKU before uniqueness checks.
     */
    protected function prepareForValidation(): void
    {
        $sku = $this->input('sku');

        if (is_string($sku)) {
            $this->merge([
                'sku' => Str::upper($sku),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nome' => ['sometimes', 'required', 'string', 'max:255'],
            'descricao' => ['sometimes', 'nullable', 'string'],
            'preco' => ['sometimes', 'required', 'numeric', 'min:0', 'decimal:0,2'],
            'categoria_id' => ['sometimes', 'required', 'integer', Rule::exists(Category::class, 'id')],
            'sku' => [
                'sometimes',
                'required',
                'string',
                'max:64',
                'alpha_dash',
                Rule::unique(Product::class, 'sku')->ignore($this->route('product')),
            ],
            'estoque' => ['sometimes', 'required', 'integer', 'min:0'],
            'status' => ['sometimes', 'required', Rule::enum(ProductStatus::class)],
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
            'preco.required' => 'Informe o preço.',
            'preco.numeric' => 'O preço deve ser numérico.',
            'preco.min' => 'O preço não pode ser negativo.',
            'preco.decimal' => 'O preço deve ter no máximo 2 casas decimais.',
            'categoria_id.required' => 'Informe a categoria.',
            'categoria_id.integer' => 'A categoria informada não existe.',
            'categoria_id.exists' => 'A categoria informada não existe.',
            'sku.required' => 'Informe o SKU.',
            'sku.max' => 'O SKU deve ter no máximo :max caracteres.',
            'sku.alpha_dash' => 'O SKU deve conter apenas letras, números, traços e underlines.',
            'sku.unique' => 'Este SKU já está em uso.',
            'estoque.required' => 'Informe o estoque.',
            'estoque.integer' => 'O estoque deve ser um número inteiro.',
            'estoque.min' => 'O estoque não pode ser negativo.',
            'status.enum' => 'O status deve ser ativo ou inativo.',
        ];
    }
}
