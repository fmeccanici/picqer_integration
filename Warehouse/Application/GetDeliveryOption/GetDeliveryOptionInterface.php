<?php


namespace App\Warehouse\Application\GetDeliveryOption;


interface GetDeliveryOptionInterface
{
    /**
     * @param GetDeliveryOptionInput $input
     * @return GetDeliveryOptionResult
     */
    public function execute(GetDeliveryOptionInput $input): GetDeliveryOptionResult;
}
