<?php

namespace App\Console\Commands;

use App\Domain\Catalog\Search\ProductIndex;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('catalog:index-products')]
#[Description('Recria o índice de produtos no Elasticsearch')]
class IndexProductsCommand extends Command
{
    /**
     * Rebuild the product index from the database.
     */
    public function handle(ProductIndex $index): int
    {
        if (! config('elasticsearch.enabled')) {
            $this->components->error('O Elasticsearch está desligado.');

            return self::FAILURE;
        }

        $count = $index->reindex();

        $this->components->info($count.' produtos indexados.');

        return self::SUCCESS;
    }
}
