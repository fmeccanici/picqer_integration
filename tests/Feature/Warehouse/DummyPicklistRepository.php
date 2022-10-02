<?php


namespace Tests\Feature\Warehouse;


use App\Warehouse\Domain\Picklists\Picklist;
use Illuminate\Support\Collection;

class DummyPicklistRepository implements \App\Warehouse\Domain\Repositories\PicklistRepositoryInterface
{
    private array $picklists;

    public function __construct()
    {
        $this->picklists = [];
    }

    /**
     * @inheritDoc
     */
    public function add(Picklist $picklist): Picklist
    {
        $this->picklists[] = $picklist;
        return $picklist;
    }

    /**
     * @inheritDoc
     */
    public function find(string $id): ?Picklist
    {
        foreach ($this->picklists as $picklist)
        {
            if ($picklist->id() == $id)
            {
                return $picklist;
            }
        }

        return null;
    }

    public function findByReference(string $reference): ?Picklist
    {
        foreach ($this->picklists as $picklist)
        {
            if ($picklist->reference() == $reference)
            {
                return $picklist;
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function update(Picklist $picklist): Picklist
    {
        foreach ($this->picklists as &$presentPicklist)
        {
            if ($presentPicklist->reference() === $picklist->reference())
            {
                $presentPicklist = $picklist;
            }
        }

        return $picklist;
    }

    /**
     * @return Picklist[]
     */
    public function all(): array
    {
        return $this->picklists;
    }


    public function deleteAll(): void
    {
        $this->picklists = [];
    }

    public function findByOrderReference(string $orderReference): ?Picklist
    {
        // TODO: Implement findByOrderReference() method.
    }

    public function findAll(): Collection
    {
        // TODO: Implement findAll() method.
    }

    public function findOneById(string $id): ?Picklist
    {
        // TODO: Implement findOneById() method.
    }
}
