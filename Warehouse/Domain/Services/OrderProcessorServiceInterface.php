<?php


namespace App\Warehouse\Domain\Services;


use App\Warehouse\Domain\Orders\Order;

interface OrderProcessorServiceInterface
{
    public function process(Order $order);
}
