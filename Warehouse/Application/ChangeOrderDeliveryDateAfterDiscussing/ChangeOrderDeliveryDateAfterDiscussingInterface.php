<?php


namespace App\Warehouse\Application\ChangeOrderDeliveryDateAfterDiscussing;


interface ChangeOrderDeliveryDateAfterDiscussingInterface
{
    /**
     * @param ChangeOrderDeliveryDateAfterDiscussingInput $input
     * @return ChangeOrderDeliveryDateAfterDiscussingResult
     */
    public function execute(ChangeOrderDeliveryDateAfterDiscussingInput $input): ChangeOrderDeliveryDateAfterDiscussingResult;
}
