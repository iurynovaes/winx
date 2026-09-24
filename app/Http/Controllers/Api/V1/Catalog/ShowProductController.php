<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;

class ShowProductController extends Controller
{
    /**
     * Display a product.
     */
    public function __invoke(Product $product): ProductResource
    {
        return new ProductResource($product);
    }
}
