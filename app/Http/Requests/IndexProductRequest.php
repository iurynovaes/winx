<?php

namespace App\Http\Requests;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Queries\ProductListFilters;
use App\Models\Category;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexProductRequest extends FormRequest
{
    private const int DEFAULT_PER_PAGE = 15;

    private const int MAX_PER_PAGE = 50;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize optional filters before validation.
     */
    protected function prepareForValidation(): void
    {
        $merge = [];

        if (is_string($this->input('nome'))) {
            $merge['nome'] = trim($this->input('nome'));
        }

        if ($this->exists('disponivel') && is_string($this->input('disponivel'))) {
            $normalized = strtolower($this->input('disponivel'));

            if (in_array($normalized, ['1', 'true'], true)) {
                $merge['disponivel'] = true;
            } elseif (in_array($normalized, ['0', 'false'], true)) {
                $merge['disponivel'] = false;
            }
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
            'nome' => ['nullable', 'string', 'max:255'],
            'categoria_id' => ['nullable', 'integer', Rule::exists(Category::class, 'id')],
            'preco_min' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'preco_max' => [
                'nullable',
                'numeric',
                'min:0',
                'decimal:0,2',
                Rule::when($this->filled('preco_min'), ['gte:preco_min']),
            ],
            'disponivel' => ['nullable', 'boolean'],
            'status' => ['nullable', Rule::enum(ProductStatus::class)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
            'page' => ['nullable', 'integer', 'min:1'],
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
            'nome.max' => 'O nome deve ter no máximo :max caracteres.',
            'categoria_id.integer' => 'A categoria informada não existe.',
            'categoria_id.exists' => 'A categoria informada não existe.',
            'preco_min.numeric' => 'O preço mínimo deve ser numérico.',
            'preco_min.min' => 'O preço mínimo não pode ser negativo.',
            'preco_min.decimal' => 'O preço mínimo deve ter no máximo 2 casas decimais.',
            'preco_max.numeric' => 'O preço máximo deve ser numérico.',
            'preco_max.min' => 'O preço máximo não pode ser negativo.',
            'preco_max.decimal' => 'O preço máximo deve ter no máximo 2 casas decimais.',
            'preco_max.gte' => 'O preço máximo deve ser maior ou igual ao preço mínimo.',
            'disponivel.boolean' => 'A disponibilidade deve ser verdadeira ou falsa.',
            'status.enum' => 'O status deve ser ativo ou inativo.',
            'per_page.integer' => 'A quantidade por página deve ser um número inteiro.',
            'per_page.min' => 'A quantidade por página deve ser no mínimo :min.',
            'per_page.max' => 'A quantidade por página deve ser no máximo :max.',
            'page.integer' => 'A página deve ser um número inteiro.',
            'page.min' => 'A página deve ser no mínimo :min.',
        ];
    }

    /**
     * Build the listing filters from validated query parameters.
     */
    public function filters(): ProductListFilters
    {
        $validated = $this->validated();

        return new ProductListFilters(
            nome: isset($validated['nome']) && $validated['nome'] !== '' ? $validated['nome'] : null,
            categoriaId: isset($validated['categoria_id']) ? (int) $validated['categoria_id'] : null,
            precoMin: isset($validated['preco_min']) ? (string) $validated['preco_min'] : null,
            precoMax: isset($validated['preco_max']) ? (string) $validated['preco_max'] : null,
            disponivel: array_key_exists('disponivel', $validated) ? (bool) $validated['disponivel'] : null,
            status: isset($validated['status']) ? ProductStatus::from($validated['status']) : null,
            perPage: (int) ($validated['per_page'] ?? self::DEFAULT_PER_PAGE),
            page: (int) ($validated['page'] ?? 1),
        );
    }
}
