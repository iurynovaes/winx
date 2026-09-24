<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Domain\Catalog\Actions\UpdateProduct;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;

class UpdateProductController extends Controller
{
    /**
     * Update a product.
     */
    public function __invoke(UpdateProductRequest $request, Product $product, UpdateProduct $update): ProductResource
    {
        $product = $update->handle($product, $request->safe()->only([
            'nome',
            'descricao',
            'preco',
            'sku',
            'categoria_id',
            'estoque',
            'status',
        ]));

        return new ProductResource($product);
    }
}
