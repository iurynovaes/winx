<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Models\Category;

class ShowCategoryController extends Controller
{
    /**
     * Display a category.
     */
    public function __invoke(Category $category): CategoryResource
    {
        return new CategoryResource($category);
    }
}
