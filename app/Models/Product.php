<?php

namespace App\Models;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Observers\ProductSearchObserver;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['sku', 'nome', 'descricao', 'preco', 'category_id', 'estoque', 'status'])]
#[ObservedBy(ProductSearchObserver::class)]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'preco' => 'decimal:2',
            'estoque' => 'integer',
            'status' => ProductStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Match a name fragment without treating the term as a LIKE pattern.
     *
     * @param  Builder<Product>  $query
     */
    #[Scope]
    protected function nomeContains(Builder $query, string $nome): void
    {
        $pattern = '%'.addcslashes($nome, '\\%_').'%';
        $operator = $query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $query->whereRaw(
            'nome '.$operator.' ? escape ?',
            [$pattern, '\\'],
        );
    }
}
