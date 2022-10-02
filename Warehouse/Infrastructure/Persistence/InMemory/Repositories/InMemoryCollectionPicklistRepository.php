<?php

namespace App\Warehouse\Infrastructure\Persistence\InMemory\Repositories;

use App\Warehouse\Domain\Picklists\Picklist;
use Illuminate\Support\Collection;

class InMemoryCollectionPicklistRepository implements \App\Warehouse\Domain\Repositories\PicklistRepositoryInterface
{
    private Collection $picklists;

    public function __construct()
    {
        $this->picklists = collect();
    }

    /**
     * @inheritDoc
     */
    public function add(Picklist $picklist): Picklist
    {
        $this->picklists->add($picklist);
        return $picklist;
    }

    /**
     * @inheritDoc
     */
    public function find(string $id): ?Picklist
    {
        return $this->picklists->first(function (Picklist $picklist) use ($id) {
            return $id === $picklist->id();
        });
    }

    /**
     * @inheritDoc
     */
    public function update(Picklist $picklist): Picklist
    {
        $this->picklists = $this->picklists->map(function (Picklist $existingPicklist) use ($picklist) {
            if ($existingPicklist->reference() === $picklist->reference())
            {
                return $picklist;
            }

            return $existingPicklist;
        });

        return $picklist;
    }

    /**
     * @inheritDoc
     */
    public function findByReference(string $picklistReference): ?Picklist
    {
        return $this->picklists->first(function (Picklist $picklist) use ($picklistReference) {
            return $picklist->reference() == $picklistReference;
        });
    }

    /**
     * @inheritDoc
     */
    public function findByOrderReference(string $orderReference): ?Picklist
    {
        return $this->picklists->first(function (Picklist $picklist) use ($orderReference) {
            return $picklist->orderReference() == $orderReference;
        });
    }

    public function findAll(): Collection
    {
        return $this->picklists;
    }

    public function findOneById(string $id): ?Picklist
    {
        return $this->picklists->first(function (Picklist $picklist) use ($id) {
            return $picklist->id() == $id;
        });
    }
}
