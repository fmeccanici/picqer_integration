<?php


namespace App\Warehouse\Application\UnsnoozePicklist;


interface UnsnoozePicklistInterface
{
    /**
     * @param UnsnoozePicklistInput $input
     * @return UnsnoozePicklistResult
     */
    public function execute(UnsnoozePicklistInput $input): UnsnoozePicklistResult;
}
