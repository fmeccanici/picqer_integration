<?php

namespace App\Warehouse\Infrastructure\Persistence\InMemory\Repositories;

use App\Warehouse\Domain\Repositories\ShipmentRepositoryInterface;
use App\Warehouse\Domain\Shipments\Shipment;
use Illuminate\Support\Collection;

class InMemoryCollectionShipmentRepository implements ShipmentRepositoryInterface
{
    protected Collection $shipments;

    public function __construct()
    {
        $this->shipments = collect();
    }

    public function findAllByOrderReference(string $orderReference): Collection
    {
        return $this->shipments->filter(function (Shipment $shipment) use ($orderReference) {
            return $shipment->orderReference() == $orderReference;
        });
    }

    /**
     * @inheritDoc
     */
    public function findOneByReference(string $reference): ?Shipment
    {
        return $this->shipments->first(function (Shipment $shipment) use ($reference) {
            return $shipment->reference() == $reference;
        });
    }

    /**
     * @inheritDoc
     */
    public function findOneByPicklistReference(string $picklistReference): ?Shipment
    {
        return $this->shipments->first(function (Shipment $shipment) use ($picklistReference) {
            return $shipment->picklistId() == $picklistReference;
        });
    }

    public function save(Shipment $shipment): void
    {
        $shipmentExists = $this->shipmentExists($shipment);

        if (! $shipmentExists)
        {
            $this->addOne($shipment);
        } else {
            $this->updateOne($shipment);
        }
    }

    private function shipmentExists(Shipment $shipment): bool
    {
        return (bool) $this->shipments->first(function (Shipment $existingShipment) use ($shipment) {
            return $existingShipment->reference() === $shipment->reference();
        });
    }

    private function addOne(Shipment $shipment)
    {
        $this->shipments->add($shipment);
    }

    private function updateOne(Shipment $shipment)
    {
        $this->shipments = $this->shipments->map(function (Shipment $existingShipment) use ($shipment) {
            if ($shipment->reference() == $existingShipment->reference())
            {
                return $shipment;
            } else {
                return $existingShipment;
            }
        });
    }

    public function findOneByOrderReference(string $orderReference): ?Shipment
    {
        return $this->shipments->first(function (Shipment $shipment) use ($orderReference) {
            return $shipment->orderReference() == $orderReference;
        });
    }
}
