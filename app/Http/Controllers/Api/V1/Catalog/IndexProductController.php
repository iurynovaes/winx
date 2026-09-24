<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Domain\Catalog\Queries\ListProducts;
use App\Http\Controllers\Controller;
use App\Http\Requests\IndexProductRequest;
use App\Http\Resources\Api\V1\ProductResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class IndexProductController extends Controller
{
    /**
     * List products using the requested filters and page size.
     */
    public function __invoke(IndexProductRequest $request, ListProducts $list): AnonymousResourceCollection
    {
        return ProductResource::collection(
            $list->handle($request->filters()),
        );
    }
}
