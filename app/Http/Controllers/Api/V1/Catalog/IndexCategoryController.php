<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class IndexCategoryController extends Controller
{
    /**
     * List categories in name order.
     */
    public function __invoke(): AnonymousResourceCollection
    {
        $categories = Category::query()
            ->orderBy('nome')
            ->orderBy('id')
            ->get();

        return CategoryResource::collection($categories);
    }
}
