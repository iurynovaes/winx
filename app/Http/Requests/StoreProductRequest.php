<?php

namespace App\Http\Requests;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize the SKU and default the status before validation.
     */
    protected function prepareForValidation(): void
    {
        $merge = [];
        $sku = $this->input('sku');

        if (is_string($sku)) {
            $merge['sku'] = Str::upper($sku);
        }

        if (! $this->exists('status')) {
            $merge['status'] = ProductStatus::Ativo->value;
        }

        if ($merge !== []) {
            $this->merge($merge);
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
            'nome' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string'],
            'preco' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'categoria_id' => ['required', 'integer', Rule::exists(Category::class, 'id')],
            'sku' => ['required', 'string', 'max:64', 'alpha_dash', Rule::unique(Product::class, 'sku')],
            'estoque' => ['required', 'integer', 'min:0'],
            'status' => ['required', Rule::enum(ProductStatus::class)],
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
