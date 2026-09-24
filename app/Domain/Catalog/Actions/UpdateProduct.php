<?php

namespace App\Domain\Catalog\Actions;

use App\Models\Product;

class UpdateProduct
{
    /**
     * Apply a partial or full update from validated attributes.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Product $product, array $attributes): Product
    {
        if (array_key_exists('categoria_id', $attributes)) {
            $attributes['category_id'] = $attributes['categoria_id'];
            unset($attributes['categoria_id']);
        }

        $product->fill($attributes);
        $product->save();

        return $product;
    }
}
