<?php

namespace App\Domain\Catalog\Actions;

use App\Models\Category;

class CreateCategory
{
    /**
     * Persist a category.
     */
    public function handle(string $nome): Category
    {
        return Category::query()->create([
            'nome' => $nome,
        ]);
    }
}
