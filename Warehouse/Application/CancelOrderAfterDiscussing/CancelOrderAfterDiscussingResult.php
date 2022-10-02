<?php


namespace App\Warehouse\Application\CancelOrderAfterDiscussing;


use App\Warehouse\Domain\Orders\Order;

final class CancelOrderAfterDiscussingResult
{
    protected Order $order;

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
