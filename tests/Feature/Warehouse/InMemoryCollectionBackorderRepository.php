<?php


namespace Tests\Feature\Warehouse;

use App\Warehouse\Domain\Backorders\Backorder;
use Illuminate\Support\Collection;

class InMemoryCollectionBackorderRepository implements \App\Warehouse\Domain\Repositories\BackorderRepositoryInterface
{
    /**
     * @var Collection
     */
    private Collection $backorders;

    public function __construct()
    {
        $this->backorders = collect();
    }

    public function all(): Collection
    {
        return $this->backorders;
    }

    public function addMultiple(Collection $backorders)
    {
        foreach ($backorders as $backorder)
        {
            $this->add($backorder);
        }
    }

    public function add(Backorder $backorder)
    {
        $this->backorders->push($backorder);
    }

    public function findByReference(string $reference): ?Backorder
    {
        return $this->backorders->first(function (Backorder $backorder) use ($reference) {
            return $backorder->reference() === $reference;
        });
    }

    public function update(Backorder $backorder): void
    {
        $this->backorders->transform(function (Backorder $existingBackorder) use ($backorder) {
            if ($existingBackorder->reference() === $backorder->reference())
            {
                return $backorder;
            }

            return $existingBackorder;
        });

    }

    public function findByOrderReference(string $orderReference): ?Backorder
    {
        return $this->backorders->first(function (Backorder $backorder) use ($orderReference) {
            return $backorder->orderReference() === $orderReference;
        });
    }
}
