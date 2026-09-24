<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Domain\Catalog\Actions\CreateProduct;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Resources\Api\V1\ProductResource;
use Illuminate\Http\JsonResponse;

class StoreProductController extends Controller
{
    /**
     * Store a product.
     */
    public function __invoke(StoreProductRequest $request, CreateProduct $create): JsonResponse
    {
        $product = $create->handle($request->safe()->only([
            'nome',
            'descricao',
            'preco',
            'sku',
            'categoria_id',
            'estoque',
            'status',
        ]));

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(JsonResponse::HTTP_CREATED);
    }
}
