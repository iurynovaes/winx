<?php

namespace App\Domain\Catalog\Actions;

use App\Domain\Activity\Enums\ProductActivityAction;
use App\Domain\Activity\Events\ProductChanged;
use App\Models\Product;

class CreateProduct
{
    /**
     * Persist a product from validated attributes.
     *
     * @param  array{nome: string, descricao?: string|null, preco: numeric-string|int|float, categoria_id: int, sku: string, estoque: int, status: string}  $attributes
     */
    public function handle(array $attributes): Product
    {
        $product = Product::query()->create([
            'nome' => $attributes['nome'],
            'descricao' => $attributes['descricao'] ?? null,
            'preco' => $attributes['preco'],
            'category_id' => $attributes['categoria_id'],
            'sku' => $attributes['sku'],
            'estoque' => $attributes['estoque'],
            'status' => $attributes['status'],
        ]);

        ProductChanged::dispatch(
            ProductActivityAction::Criado,
            $product->id,
            null,
            $product->activitySnapshot(),
        );

        return $product;
    }
}
