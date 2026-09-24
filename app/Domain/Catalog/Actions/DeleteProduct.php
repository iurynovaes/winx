<?php

namespace App\Domain\Catalog\Actions;

use App\Models\Product;

class DeleteProduct
{
    /**
     * Remove the product.
     */
    public function handle(Product $product): void
    {
        $product->delete();
    }
}
