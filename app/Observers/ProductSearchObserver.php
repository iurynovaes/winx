<?php

namespace App\Observers;

use App\Jobs\SyncProductSearch;
use App\Models\Product;

class ProductSearchObserver
{
    /**
     * Queue a sync after the product is stored.
     */
    public function saved(Product $product): void
    {
        $this->queue($product);
    }

    /**
     * Queue a sync after the product is removed.
     */
    public function deleted(Product $product): void
    {
        $this->queue($product);
    }

    private function queue(Product $product): void
    {
        if (! config('elasticsearch.enabled')) {
            return;
        }

        SyncProductSearch::dispatch($product->id)->afterCommit();
    }
}
