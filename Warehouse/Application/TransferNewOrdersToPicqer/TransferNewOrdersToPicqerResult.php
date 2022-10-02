<?php


namespace App\Warehouse\Application\TransferNewOrdersToPicqer;


use App\Warehouse\Domain\Orders\Order;
use Illuminate\Support\Collection;

final class TransferNewOrdersToPicqerResult
{
    private Collection $processedOrders;
    private Collection $failedOrders;

    /**
     * AddAndProcessToBeProcessedOrdersResult constructor.
     * @param Collection $processedOrders
     * @param Collection $failedOrders
     */
    public function __construct(Collection $processedOrders, Collection $failedOrders)
    {
        $this->processedOrders = $processedOrders;
        $this->failedOrders = $failedOrders;
    }

    /**
     * @return Collection<Order>
     */
    public function processedOrders(): Collection
    {
        return $this->processedOrders;
    }

    /**
     * @return Collection<Order>
     */
    public function failedOrders(): Collection
    {
        return $this->failedOrders;
    }
}
