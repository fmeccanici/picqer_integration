<?php


namespace App\Warehouse\Domain\Repositories;


use App\Warehouse\Domain\Orders\OrderedItem;

interface OrderFlowRepositoryInterface
{
    public function findByOrderedItem(OrderedItem $orderedItem);
}
