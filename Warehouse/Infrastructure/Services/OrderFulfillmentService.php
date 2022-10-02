<?php

namespace App\Warehouse\Infrastructure\Services;

use App\Warehouse\Domain\Orders\Order;
use App\Warehouse\Infrastructure\Persistence\MsSql\Repositories\MsSqlOrderRepository;
use App\Warehouse\Infrastructure\Persistence\Picqer\Repositories\PicqerOrderRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class OrderFulfillmentService implements \App\Warehouse\Domain\Services\OrderFulfillmentServiceInterface
{
    /**
     * @inheritDoc
     */
    public function isFulfilled(Order $order): bool
    {
        $orderReference = $order->reference();

        if (Str::contains($order->reference(), '-'))
        {
            $orderReference = explode('-', $order->reference())[0];
        }

        $orderRepositories = $this->orderRepositories();

        foreach ($orderRepositories as $orderRepository)
        {
            $orders = $orderRepository->findAllByReference($orderReference);

            foreach ($orders as $order)
            {
                // This is done to prevent ! $order->completed() to return true when we already have a new order
                if ($order->cancelled())
                {
                    continue;
                }

                if (! $order->completed() && ! $order->noReview())
                {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @return Collection
     */
    public function orderRepositories(): Collection
    {
        $msSqlOrderRepository = App::make(MsSqlOrderRepository::class);
        $picqerOrderRepository = App::make(PicqerOrderRepository::class);

        return collect(array($msSqlOrderRepository, $picqerOrderRepository));
    }
}
