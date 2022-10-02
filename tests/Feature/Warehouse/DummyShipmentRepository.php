<?php

namespace Tests\Feature\Warehouse;

use App\Warehouse\Domain\Shipments\Shipment;
use Illuminate\Support\Collection;

class DummyShipmentRepository implements \App\Warehouse\Domain\Repositories\ShipmentRepositoryInterface
{

    public function findAllByOrderReference(string $orderReference): Collection
    {
        return collect();
    }

    /**
     * @inheritDoc
     */
    public function findOneByReference(string $reference): ?Shipment
    {
        return null;
    }

    public function findOneByPicklistReference(string $picklistReference): ?Shipment
    {
        // TODO: Implement findOneByPicklistReference() method.
    }

    public function save(Shipment $shipment): void
    {
        // TODO: Implement save() method.
    }

    public function findOneByOrderReference(string $orderReference): ?Shipment
    {
        // TODO: Implement findOneByOrderReference() method.
    }
}
