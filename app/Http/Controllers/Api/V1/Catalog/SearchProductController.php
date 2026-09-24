<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Domain\Catalog\Search\ProductIndex;
use App\Http\Controllers\Controller;
use App\Http\Requests\SearchProductRequest;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class SearchProductController extends Controller
{
    /**
     * Search products by relevance.
     */
    public function __invoke(SearchProductRequest $request, ProductIndex $index): AnonymousResourceCollection|JsonResponse
    {
        if (! config('elasticsearch.enabled')) {
            return new JsonResponse([
                'message' => 'A busca inteligente está desligada.',
            ], JsonResponse::HTTP_SERVICE_UNAVAILABLE);
        }

        $perPage = (int) ($request->validated('per_page') ?? 15);
        $page = (int) ($request->validated('page') ?? 1);
        $result = $index->search($request->string('q')->toString(), $perPage, $page);
        $positions = array_flip($result['ids']);

        $products = Product::query()
            ->with('category')
            ->whereIn('id', $result['ids'])
            ->get()
            ->sortBy(fn (Product $product): int => $positions[$product->id] ?? PHP_INT_MAX)
            ->values();

        $paginator = (new LengthAwarePaginator(
            $products,
            $result['total'],
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ],
        ))->withQueryString();

        return ProductResource::collection($paginator);
    }
}
