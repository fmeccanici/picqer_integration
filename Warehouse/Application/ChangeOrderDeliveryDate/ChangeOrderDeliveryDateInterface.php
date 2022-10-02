<?php


namespace App\Warehouse\Application\ChangeOrderDeliveryDate;


interface ChangeOrderDeliveryDateInterface
{
    /**
     * @param ChangeOrderDeliveryDateInput $input
     * @return ChangeOrderDeliveryDateResult
     */
    public function execute(ChangeOrderDeliveryDateInput $input): ChangeOrderDeliveryDateResult;
}
