<?php


namespace App\Warehouse\Domain\Repositories;


use App\Warehouse\Domain\Orders\OrderedItem;
use Illuminate\Support\Collection;

interface OrderedItemRepositoryInterface
{
    public function findByOrderReference(string $orderReference): Collection;
    public function updateMultiple(Collection $orderedItems);
    public function add(OrderedItem $orderedItem): void;
    public function addMultiple(Collection $orderedItems): void;
}
