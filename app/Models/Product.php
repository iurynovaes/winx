<?php

namespace App\Models;

use App\Domain\Catalog\Enums\ProductStatus;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'sku',
        'nome',
        'descricao',
        'preco',
        'category_id',
        'estoque',
        'status',
    ];

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
     * Capture the fields stored in the activity log.
     *
     * @return array{id: int, sku: string, nome: string, descricao: string|null, preco: string, category_id: int, estoque: int, status: string}
     */
    public function activitySnapshot(): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'nome' => $this->nome,
            'descricao' => $this->descricao,
            'preco' => $this->preco,
            'category_id' => $this->category_id,
            'estoque' => $this->estoque,
            'status' => $this->status->value,
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
    public function scopeNomeContains(Builder $query, string $nome): void
    {
        $pattern = '%'.addcslashes($nome, '\\%_').'%';
        $operator = $query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $query->whereRaw(
            'nome '.$operator.' ? escape ?',
            [$pattern, '\\'],
        );
    }
}
