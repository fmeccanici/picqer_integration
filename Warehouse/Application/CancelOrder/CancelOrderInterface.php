<?php


namespace App\Warehouse\Application\CancelOrder;


interface CancelOrderInterface
{
    /**
     * @param CancelOrderInput $input
     * @return CancelOrderResult
     */
    public function execute(CancelOrderInput $input): CancelOrderResult;
}
