<?php


namespace Tests\Feature\Warehouse;


use App\Warehouse\Domain\Orders\OrderedItem;
use Illuminate\Support\Collection;

class DummyOrderedItemRepository implements \App\Warehouse\Domain\Repositories\OrderedItemRepositoryInterface
{
    /**
     * @var Collection
     */
    private Collection $orderedItems;

    public function __construct()
    {
        $this->orderedItems = collect();
    }

    public function findByOrderReference(string $orderReference): Collection
    {
        $result = collect();

        foreach ($this->orderedItems as $orderedItem)
        {
            if ($orderedItem->orderReference() === $orderReference)
            {
                $result->push($orderedItem);
            }
        }

        return $result;
    }

    public function updateMultiple(Collection $orderedItems)
    {
        foreach ($orderedItems as $orderedItem)
        {
            $key = $this->orderedItems->search(function(OrderedItem $existingOrderedItem) use ($orderedItem) {
                return $orderedItem->orderReference() === $existingOrderedItem->orderReference();
            });

            $this->orderedItems->replace([
                $key => $orderedItem
            ]);
        }
    }

    public function add(OrderedItem $orderedItem): void
    {
        $this->orderedItems->push($orderedItem);
    }

    public function addMultiple(Collection $orderedItems): void
    {
        foreach ($orderedItems as $orderedItem)
        {
            $this->orderedItems->push($orderedItem);
        }
    }
}
