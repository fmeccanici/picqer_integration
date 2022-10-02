<?php

namespace Tests\Feature\Warehouse;

use App\Warehouse\Domain\Orders\Order;
use Illuminate\Support\Collection;

class DummyOrderFulfillmentService implements \App\Warehouse\Domain\Services\OrderFulfillmentServiceInterface
{

    /**
     * @inheritDoc
     */
    public function isFulfilled(Order $order): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function orderRepositories(): Collection
    {
        return collect();
    }
}
