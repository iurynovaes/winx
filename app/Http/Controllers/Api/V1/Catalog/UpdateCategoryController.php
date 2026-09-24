<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Domain\Catalog\Actions\UpdateCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Models\Category;

class UpdateCategoryController extends Controller
{
    /**
     * Rename a category.
     */
    public function __invoke(UpdateCategoryRequest $request, Category $category, UpdateCategory $update): CategoryResource
    {
        $category = $update->handle($category, $request->string('nome')->toString());

        return new CategoryResource($category);
    }
}
