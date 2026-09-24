<?php

namespace App\Jobs;

use App\Domain\Catalog\Search\ProductIndex;
use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncProductSearch implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [1, 5, 10];

    public int $timeout = 15;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $productId) {}

    /**
     * Index the current product, or remove the document when it no longer exists.
     */
    public function handle(ProductIndex $index): void
    {
        if (! config('elasticsearch.enabled')) {
            return;
        }

        $product = Product::query()->find($this->productId);

        if ($product === null) {
            $index->delete($this->productId);

            return;
        }

        $index->upsert($product);
    }
}
