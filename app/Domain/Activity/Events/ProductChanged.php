<?php

namespace App\Domain\Activity\Events;

use App\Domain\Activity\Enums\ProductActivityAction;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class ProductChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public ?int $userId;

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function __construct(
        public ProductActivityAction $action,
        public int $productId,
        public ?array $before,
        public ?array $after,
    ) {
        $userId = auth()->id();
        $this->userId = $userId !== null ? (int) $userId : null;
    }
}
