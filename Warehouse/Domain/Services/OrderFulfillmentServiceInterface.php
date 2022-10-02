<?php

namespace App\Warehouse\Domain\Services;

use App\Warehouse\Domain\Orders\Order;
use App\Warehouse\Domain\Repositories\OrderRepositoryInterface;
use Illuminate\Support\Collection;

interface OrderFulfillmentServiceInterface
{
    /**
     * @param Order $order
     * @return bool
     */
    public function isFulfilled(Order $order): bool;

    /**
     * @return Collection<OrderRepositoryInterface>
     */
    public function orderRepositories(): Collection;

}
