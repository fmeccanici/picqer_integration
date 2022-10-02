<?php


namespace App\Warehouse\Application\HandlePicklistCreated;


use App\Warehouse\Domain\Picklists\Picklist;

final class HandlePicklistCreatedResult
{
    private Picklist $picklist;

    /**
     * HandlePicklistCreatedResult constructor.
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
