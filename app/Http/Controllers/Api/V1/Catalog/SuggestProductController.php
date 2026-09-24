<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Domain\Catalog\Search\ProductIndex;
use App\Http\Controllers\Controller;
use App\Http\Requests\SuggestProductRequest;
use Illuminate\Http\JsonResponse;

class SuggestProductController extends Controller
{
    /**
     * Suggest product names for a search prefix.
     */
    public function __invoke(SuggestProductRequest $request, ProductIndex $index): JsonResponse
    {
        if (! config('elasticsearch.enabled')) {
            return new JsonResponse([
                'message' => 'A busca inteligente está desligada.',
            ], JsonResponse::HTTP_SERVICE_UNAVAILABLE);
        }

        return new JsonResponse([
            'data' => $index->suggest($request->string('q')->toString()),
        ]);
    }
}
