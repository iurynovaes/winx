<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Domain\Catalog\Actions\CreateCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Resources\Api\V1\CategoryResource;
use Illuminate\Http\JsonResponse;

class StoreCategoryController extends Controller
{
    /**
     * Store a category.
     */
    public function __invoke(StoreCategoryRequest $request, CreateCategory $create): JsonResponse
    {
        $category = $create->handle($request->string('nome')->toString());

        return (new CategoryResource($category))
            ->response()
            ->setStatusCode(JsonResponse::HTTP_CREATED);
    }
}
