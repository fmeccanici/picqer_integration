<?php


namespace App\Warehouse\Domain\Repositories;


use App\Warehouse\Domain\Orders\Order;
use Illuminate\Support\Collection;

interface OrderRepositoryInterface
{
    /**
     * @param Order $order
     * @return void
     */
    public function add(Order $order): Order;

    /**
     * @param string $id
     * @return Order|null
     */
    public function find(string $id): ?Order;

    /**
     * @param string $reference
     * @param bool $lazyLoadPicklists
     * @return Order|null
     */
    public function findOneByReference(string $reference, bool $lazyLoadPicklists = false): ?Order;

    /**
     * @param Order $order
     * @param array $attributes
     * @return Order
     */
    // TODO: Task 19471: Verbeter de Picqer Order Mapper (met name de toEntity methode)
    // Hierdoor is de attributes array niet meer nodig
    public function update(Order $order, array $attributes = ['tags']): Order;

    /**
     * @return Collection<Order>
     */
    public function findNewOrders(): Collection;

    /**
     * @param Collection $orders
     * @return mixed
     */
    public function addMultiple(Collection $orders);

    /**
     * @return Collection
     */
    public function findNewOrderReferences(): Collection;

    /**
     * @return Collection
     */
    public function findAll(): Collection;

    /**
     * @param string $reference
     * @return Collection
     */
    public function findAllByReference(string $reference): Collection;

    public function updateCouponDiscountCodeId(Order $order): Order;
}
