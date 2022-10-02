<?php


namespace Tests\Feature\Warehouse;


use App\Warehouse\Domain\Orders\Order;

class DummyOrderProcessorService implements \App\Warehouse\Domain\Services\OrderProcessorServiceInterface
{
    private array $processedOrders;

    public function __construct()
    {
        $this->processedOrders = [];
    }

    public function process(Order $order)
    {
        $this->processedOrders[] = $order;
    }

    public function processedOrders(): array
    {
        return $this->processedOrders;
    }
}
