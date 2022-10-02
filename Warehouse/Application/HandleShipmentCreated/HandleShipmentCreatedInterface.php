<?php


namespace App\Warehouse\Application\HandleShipmentCreated;


interface HandleShipmentCreatedInterface
{
    /**
     * @param HandleShipmentCreatedInput $input
     * @return HandleShipmentCreatedResult
     */
    public function execute(HandleShipmentCreatedInput $input): HandleShipmentCreatedResult;
}
