<?php


namespace Tests\Feature\Warehouse;


use App\Warehouse\Domain\Orders\Order;
use Illuminate\Support\Collection;

class DummyOrderRepository implements \App\Warehouse\Domain\Repositories\OrderRepositoryInterface
{

    /**
     * @var Order[]
     */
    private array $orders;

    public function __construct()
    {
        $this->orders = [];
    }

    /**
     * @inheritDoc
     */
    public function add(Order $order): Order
    {
        // Clone needed to prevent changing the order by reference in a test
        $this->orders[] = clone $order;
        return $order;
    }

    /**
     * @inheritDoc
     */
    public function find(string $id): ?Order
    {
        foreach ($this->orders as $order)
        {
            if ($order->id() === $id)
            {
                return $order;
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function findOneByReference(string $reference, bool $lazyLoadPicklists = false): ?Order
    {
        foreach ($this->orders as $order)
        {
            if ($order->reference() === $reference)
            {
                return $order;
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function update(Order $order, array $attributes = ['tags']): Order
    {
        $foundOrder = $this->findOneByReference($order->reference());
        $foundOrder->changePicklists($order->picklists());
        return $foundOrder;
    }

    /**
     * @inheritDoc
     */
    public function getToBeProcessed(): array
    {
        $result = [];
        foreach ($this->orders as $order)
        {
            if ($order->status() === "unprocessed")
            {
                $result[] = $order;
            }
        }

        return $result;
    }

    public function processOrder(string $reference): void
    {
        // TODO: Implement processOrder() method.
    }

    public function all(): array
    {
        return $this->orders;
    }

    public function updateStatus(Order $order): Order
    {
        return $this->update($order);
    }

    public function findByOrderShipmentId(string $orderShipmentId): ?Order
    {
        foreach ($this->orders as $order)
        {
            $picklist = $order->picklists()->first();

            if ($picklist === null)
            {
                return null;
            }

            if ($picklist->reference() === $orderShipmentId)
            {
                return $order;
            }
        }

        return null;
    }

    public function findNewOrders(): Collection
    {
        $result = [];
        foreach ($this->orders as $order)
        {
            if ($order->new())
            {
                $result[] = $order;
            }
        }

        return collect($result);
    }

    public function addMultiple(Collection $orders)
    {
        $this->orders = array_merge($this->orders, $orders->all());
    }

    public function findNewOrderReferences(): Collection
    {
        return $this->findNewOrders()->map->reference();
    }

    public function findAll(): Collection
    {
        return collect($this->orders);
    }

    public function findAllByReference(string $reference): Collection
    {
        // TODO: Implement findAllByReference() method.
    }
}
