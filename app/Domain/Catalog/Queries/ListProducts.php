<?php

namespace App\Domain\Catalog\Queries;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListProducts
{
    /**
     * Return a page of products matching the listing filters.
     *
     * @return LengthAwarePaginator<int, Product>
     */
    public function handle(ProductListFilters $filters): LengthAwarePaginator
    {
        $query = Product::query()->with('category');

        if (is_string($filters->nome) && $filters->nome !== '') {
            $query->nomeContains($filters->nome);
        }

        if ($filters->categoriaId !== null) {
            $query->where('category_id', $filters->categoriaId);
        }

        if ($filters->precoMin !== null) {
            $query->where('preco', '>=', $filters->precoMin);
        }

        if ($filters->precoMax !== null) {
            $query->where('preco', '<=', $filters->precoMax);
        }

        if ($filters->disponivel === true) {
            $query->where('estoque', '>', 0);
        }

        if ($filters->disponivel === false) {
            $query->where('estoque', 0);
        }

        if ($filters->status !== null) {
            $query->where('status', $filters->status);
        }

        return $query
            ->orderBy('nome')
            ->orderBy('id')
            ->paginate(perPage: $filters->perPage, page: $filters->page)
            ->withQueryString();
    }
}
