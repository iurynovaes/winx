<?php

namespace App\Domain\Catalog\Queries;

use App\Domain\Catalog\Enums\ProductStatus;

final readonly class ProductListFilters
{
    public function __construct(
        public ?string $nome,
        public ?int $categoriaId,
        public ?string $precoMin,
        public ?string $precoMax,
        public ?bool $disponivel,
        public ?ProductStatus $status,
        public int $perPage,
        public int $page,
    ) {}
}
