<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{id: int, sku: string, nome: string, descricao: string|null, preco: string, categoria: array{id: int, nome: string}, estoque: int, status: string, created_at: string|null, updated_at: string|null}
     */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing('category');

        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'nome' => $this->nome,
            'descricao' => $this->descricao,
            'preco' => $this->preco,
            'categoria' => [
                'id' => $this->category->id,
                'nome' => $this->category->nome,
            ],
            'estoque' => $this->estoque,
            'status' => $this->status->value,
            'created_at' => $this->created_at?->toJSON(),
            'updated_at' => $this->updated_at?->toJSON(),
        ];
    }
}
