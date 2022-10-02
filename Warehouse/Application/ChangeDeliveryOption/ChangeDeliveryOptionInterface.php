<?php


namespace App\Warehouse\Application\ChangeDeliveryOption;


interface ChangeDeliveryOptionInterface
{
    /**
     * @param ChangeDeliveryOptionInput $input
     * @return ChangeDeliveryOptionResult
     */
    public function execute(ChangeDeliveryOptionInput $input): ChangeDeliveryOptionResult;
}
