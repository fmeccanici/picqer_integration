<?php


namespace App\Warehouse\Application\ChangeDeliveryOption;


use App\Warehouse\Domain\Orders\DeliveryOption;
use App\Warehouse\Domain\Orders\Order;

final class ChangeDeliveryOptionResult
{
    private Order $order;

    /**
     * @param Order $order
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function order(): Order
    {
        return $this->order;
    }
}
