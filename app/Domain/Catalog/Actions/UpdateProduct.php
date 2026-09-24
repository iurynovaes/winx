<?php

namespace App\Domain\Catalog\Actions;

use App\Domain\Activity\Enums\ProductActivityAction;
use App\Domain\Activity\Events\ProductChanged;
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

        $before = $product->activitySnapshot();
        $product->fill($attributes);

        if (! $product->isDirty()) {
            return $product;
        }

        $product->save();

        ProductChanged::dispatch(
            ProductActivityAction::Atualizado,
            $product->id,
            $before,
            $product->activitySnapshot(),
        );

        return $product;
    }
}
