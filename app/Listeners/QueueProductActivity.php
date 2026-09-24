<?php

namespace App\Listeners;

use App\Domain\Activity\Events\ProductChanged;
use App\Jobs\RecordProductActivity;

class QueueProductActivity
{
    /**
     * Queue the activity log after the product change has committed.
     */
    public function handle(ProductChanged $event): void
    {
        RecordProductActivity::dispatch(
            $event->action,
            $event->productId,
            $event->userId,
            $event->before,
            $event->after,
        );
    }
}
