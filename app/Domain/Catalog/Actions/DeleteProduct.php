<?php

namespace App\Domain\Catalog\Actions;

use App\Domain\Activity\Enums\ProductActivityAction;
use App\Domain\Activity\Events\ProductChanged;
use App\Models\Product;

class DeleteProduct
{
    /**
     * Remove the product.
     */
    public function handle(Product $product): void
    {
        $before = $product->activitySnapshot();
        $productId = $product->id;

        $product->delete();

        ProductChanged::dispatch(
            ProductActivityAction::Excluido,
            $productId,
            $before,
            null,
        );
    }
}
