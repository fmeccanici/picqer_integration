<?php


namespace App\Warehouse\Application\TransferNewOrdersToPicqer;


interface TransferNewOrdersToPicqerInterface
{
    /**
     * @param TransferNewOrdersToPicqerInput $input
     * @return TransferNewOrdersToPicqerResult
     */
    public function execute(TransferNewOrdersToPicqerInput $input): TransferNewOrdersToPicqerResult;
}
