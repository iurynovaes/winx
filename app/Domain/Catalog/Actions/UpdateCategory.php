<?php

namespace App\Domain\Catalog\Actions;

use App\Models\Category;

class UpdateCategory
{
    /**
     * Rename a category.
     */
    public function handle(Category $category, string $nome): Category
    {
        $category->fill([
            'nome' => $nome,
        ]);
        $category->save();

        return $category;
    }
}
