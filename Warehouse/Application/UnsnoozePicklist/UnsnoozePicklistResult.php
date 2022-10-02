<?php


namespace App\Warehouse\Application\UnsnoozePicklist;


use App\Warehouse\Domain\Picklists\Picklist;

final class UnsnoozePicklistResult
{
    private Picklist $picklist;

    /**
     * SnoozePicklistResult constructor.
     * @param Picklist $picklist
     */
    public function __construct(Picklist $picklist)
    {
        $this->picklist = $picklist;
    }

    public function picklist(): Picklist
    {
        return $this->picklist;
    }
}
