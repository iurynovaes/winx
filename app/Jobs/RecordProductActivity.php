<?php

namespace App\Jobs;

use App\Domain\Activity\Enums\ProductActivityAction;
use App\Models\ProductActivity;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RecordProductActivity implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function __construct(
        public ProductActivityAction $action,
        public int $productId,
        public ?int $userId,
        public ?array $before,
        public ?array $after,
    ) {}

    /**
     * Persist the product activity log.
     */
    public function handle(): void
    {
        ProductActivity::query()->create([
            'user_id' => $this->userId,
            'product_id' => $this->productId,
            'action' => $this->action,
            'before' => $this->before,
            'after' => $this->after,
        ]);
    }
}
