<?php

namespace App\Warehouse\Domain\Repositories;

use App\Warehouse\Domain\Shipments\Shipment;
use Illuminate\Support\Collection;

interface ShipmentRepositoryInterface
{
    public function findAllByOrderReference(string $orderReference): Collection;

    /**
     * @param string $reference
     * @return Shipment|null
     */
    public function findOneByReference(string $reference): ?Shipment;

    /**
     * @param string $picklistReference
     * @return Shipment|null
     */
    public function findOneByPicklistReference(string $picklistReference): ?Shipment;

    /**
     * @param string $orderReference
     * @return Shipment|null
     */
    public function findOneByOrderReference(string $orderReference): ?Shipment;


    /**
     * @param Shipment $shipment
     * @return void
     */
    public function save(Shipment $shipment): void;
}
