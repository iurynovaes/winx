<?php

namespace App\Domain\Catalog\Search;

use App\Models\Product;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ProductIndex
{
    /**
     * Replace the index with the products currently stored in the database.
     */
    public function reindex(): int
    {
        $this->ensureIndex();

        $count = 0;

        Product::query()
            ->orderBy('id')
            ->chunkById(200, function (Collection $products) use (&$count): void {
                $this->bulk($products);
                $count += $products->count();
            });

        return $count;
    }

    /**
     * Create or replace the product document.
     */
    public function upsert(Product $product): void
    {
        $this->ensureIndex();

        $this->request()
            ->put($this->index().'/_doc/'.$product->id, $this->document($product))
            ->throw();
    }

    /**
     * Remove the product document. A missing document is already in sync.
     */
    public function delete(int $productId): void
    {
        $this->ensureIndex();

        $response = $this->request()->delete($this->index().'/_doc/'.$productId);

        if ($response->notFound()) {
            return;
        }

        $response->throw();
    }

    /**
     * Search by relevance and return the ordered ids of one page.
     *
     * @return array{ids: list<int>, total: int}
     */
    public function search(string $term, int $perPage, int $page): array
    {
        $this->ensureIndex();

        $response = $this->request()
            ->post($this->index().'/_search', [
                'from' => ($page - 1) * $perPage,
                'size' => $perPage,
                'track_total_hits' => true,
                'query' => [
                    'multi_match' => [
                        'query' => $term,
                        'fields' => ['nome^3', 'descricao', 'sku'],
                    ],
                ],
            ])
            ->throw();

        return [
            'ids' => array_map(
                intval(...),
                array_column($response->json('hits.hits') ?? [], '_id'),
            ),
            'total' => (int) $response->json('hits.total.value'),
        ];
    }

    /**
     * Return product name suggestions for the given prefix.
     *
     * @return list<string>
     */
    public function suggest(string $term): array
    {
        $this->ensureIndex();

        $response = $this->request()
            ->post($this->index().'/_search', [
                'size' => 0,
                'suggest' => [
                    'products' => [
                        'prefix' => $term,
                        'completion' => [
                            'field' => 'nome_suggest',
                            'size' => 5,
                            'skip_duplicates' => true,
                        ],
                    ],
                ],
            ])
            ->throw();

        $options = $response->json('suggest.products.0.options') ?? [];

        return array_values(array_map(
            fn (array $option): string => $option['text'],
            $options,
        ));
    }

    /**
     * @param  Collection<int, Product>  $products
     */
    private function bulk(Collection $products): void
    {
        if ($products->isEmpty()) {
            return;
        }

        $lines = [];

        foreach ($products as $product) {
            $lines[] = json_encode(['index' => ['_id' => $product->id]], JSON_THROW_ON_ERROR);
            $lines[] = json_encode($this->document($product), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        }

        $response = $this->request()
            ->withBody(implode("\n", $lines)."\n", 'application/x-ndjson')
            ->post($this->index().'/_bulk')
            ->throw();

        if ($response->json('errors') === true) {
            throw new RuntimeException('Falha ao indexar produtos no Elasticsearch.');
        }
    }

    private function ensureIndex(): void
    {
        $response = $this->request()->put($this->index(), [
            'settings' => [
                'analysis' => [
                    'analyzer' => [
                        'product_text' => [
                            'tokenizer' => 'standard',
                            'filter' => ['lowercase', 'asciifolding'],
                        ],
                    ],
                ],
            ],
            'mappings' => [
                'properties' => [
                    'nome' => ['type' => 'text', 'analyzer' => 'product_text'],
                    'nome_suggest' => ['type' => 'completion'],
                    'descricao' => ['type' => 'text', 'analyzer' => 'product_text'],
                    'sku' => ['type' => 'text', 'analyzer' => 'product_text'],
                ],
            ],
        ]);

        if ($response->status() === 400 && $response->json('error.type') === 'resource_already_exists_exception') {
            return;
        }

        $response->throw();
    }

    /**
     * @return array{nome: string, nome_suggest: string, descricao: string|null, sku: string}
     */
    private function document(Product $product): array
    {
        return [
            'nome' => $product->nome,
            'nome_suggest' => $product->nome,
            'descricao' => $product->descricao,
            'sku' => $product->sku,
        ];
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl($this->host())
            ->connectTimeout(2)
            ->timeout(5)
            ->acceptJson();
    }

    private function host(): string
    {
        return rtrim((string) config('elasticsearch.host'), '/');
    }

    private function index(): string
    {
        $index = (string) config('elasticsearch.index');

        if (preg_match('/^[a-z0-9][a-z0-9_-]*$/', $index) !== 1) {
            throw new RuntimeException('O nome do índice do Elasticsearch é inválido.');
        }

        return $index;
    }
}
