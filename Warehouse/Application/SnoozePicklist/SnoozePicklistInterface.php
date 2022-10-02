<?php


namespace App\Warehouse\Application\SnoozePicklist;


interface SnoozePicklistInterface
{
    /**
     * @param SnoozePicklistInput $input
     * @return SnoozePicklistResult
     */
    public function execute(SnoozePicklistInput $input): SnoozePicklistResult;
}
