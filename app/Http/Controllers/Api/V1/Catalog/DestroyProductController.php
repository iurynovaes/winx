<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Domain\Catalog\Actions\DeleteProduct;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Response;

class DestroyProductController extends Controller
{
    /**
     * Delete a product.
     */
    public function __invoke(Product $product, DeleteProduct $delete): Response
    {
        $delete->handle($product);

        return response()->noContent();
    }
}
