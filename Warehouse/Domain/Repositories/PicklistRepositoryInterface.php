<?php


namespace App\Warehouse\Domain\Repositories;


use App\Warehouse\Domain\Picklists\Picklist;
use Illuminate\Support\Collection;

interface PicklistRepositoryInterface
{
    /**
     * @param Picklist $picklist
     * @return void
     */
    public function add(Picklist $picklist): Picklist;

    /**
     * @param string $id
     * @return Picklist|null
     */
    public function find(string $id): ?Picklist;

    /**
     * @param Picklist $picklist
     * @return Picklist
     */
    public function update(Picklist $picklist): Picklist;

    /**
     * @param string $picklistReference
     * @return Picklist|null
     */
    public function findByReference(string $picklistReference): ?Picklist;

    /**
     * @param string $id
     * @return Picklist|null
     */
    public function findOneById(string $id): ?Picklist;


    /**
     * @param string $orderReference
     * @return Picklist|null
     */
    public function findByOrderReference(string $orderReference): ?Picklist;

    /**
     * @return Collection
     */
    public function findAll(): Collection;
}
