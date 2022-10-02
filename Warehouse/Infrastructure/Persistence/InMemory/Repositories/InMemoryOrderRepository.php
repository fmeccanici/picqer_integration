<?php

namespace App\Warehouse\Infrastructure\Persistence\InMemory\Repositories;

use App\Warehouse\Domain\Orders\Order;
use App\Warehouse\Domain\Repositories\OrderRepositoryInterface;
use Illuminate\Support\Collection;

class InMemoryOrderRepository implements OrderRepositoryInterface
{
    private Collection $orders;

    public function __construct()
    {
        $this->orders = collect();
    }

    /**
     * @inheritDoc
     */
    public function add(Order $order): Order
    {
        $this->orders->push($order);
        return $order;
    }

    /**
     * @inheritDoc
     */
    public function find(string $id): ?Order
    {

    }

    /**
     * @inheritDoc
     */
    public function findOneByReference(string $reference, bool $lazyLoadPicklists = false): ?Order
    {
        return $this->orders->first(function (Order $order) use($reference) {
                return $order->reference() === $reference;
        });
    }

    /**
     * @inheritDoc
     */
    public function update(Order $order, array $attributes = ['tags']): Order
    {
        $this->orders = $this->orders->map(function (Order $existingOrder) use ($order){
            if ($order->reference() === $existingOrder->reference())
            {
                return $order;
            } else {
                return $existingOrder;
            }
        });

        return $order;
    }

    /**
     * @inheritDoc
     */
    public function findNewOrders(): Collection
    {
        return $this->orders->filter(function (Order $order) {
            return $order->new();
        });
    }

    /**
     * @inheritDoc
     */
    public function addMultiple(Collection $orders)
    {
        $this->orders = $this->orders->merge($orders);
    }

    /**
     * @inheritDoc
     */
    public function findNewOrderReferences(): Collection
    {
        return $this->findNewOrders()->map->reference();
    }

    /**
     * @inheritDoc
     */
    public function findAll(): Collection
    {
        return $this->orders;
    }

    /**
     * @param string $reference
     * @return Collection
     */
    public function findAllByReference(string $reference): Collection
    {
        // TODO: Implement findAllByReference() method.
    }

    /**
     * @return void
     */
    public function deleteAll(): void
    {
        $this->orders = collect();
    }

    public function updateCouponDiscountCodeId(Order $order): Order
    {
        return $this->update($order);
    }
}
