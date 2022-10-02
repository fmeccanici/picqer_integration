<?php


namespace App\Warehouse\Application\CancelOrderAfterDiscussing;


interface CancelOrderAfterDiscussingInterface
{
    /**
     * @param CancelOrderAfterDiscussingInput $input
     * @return CancelOrderAfterDiscussingResult
     */
    public function execute(CancelOrderAfterDiscussingInput $input): CancelOrderAfterDiscussingResult;
}
