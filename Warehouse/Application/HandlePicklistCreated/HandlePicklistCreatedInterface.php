<?php


namespace App\Warehouse\Application\HandlePicklistCreated;


interface HandlePicklistCreatedInterface
{
    /**
     * @param HandlePicklistCreatedInput $input
     * @return HandlePicklistCreatedResult
     */
    public function execute(HandlePicklistCreatedInput $input): HandlePicklistCreatedResult;
}
