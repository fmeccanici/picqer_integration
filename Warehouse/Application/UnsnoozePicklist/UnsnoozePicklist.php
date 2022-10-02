<?php


namespace App\Warehouse\Application\UnsnoozePicklist;

use App\Warehouse\Application\SnoozePicklist\SnoozePicklistInput;
use App\Warehouse\Application\SnoozePicklist\SnoozePicklistResult;
use App\Warehouse\Domain\Repositories\PicklistRepositoryInterface;
use Carbon\CarbonImmutable;

class UnsnoozePicklist implements UnsnoozePicklistInterface
{
    /**
     * @var PicklistRepositoryInterface
     */
    private PicklistRepositoryInterface $picklistRepository;

    /**
     * SnoozePicklist constructor.
     * @param PicklistRepositoryInterface $picklistRepository
     */
    public function __construct(PicklistRepositoryInterface $picklistRepository)
    {
        $this->picklistRepository = $picklistRepository;
    }

    /**
     * @inheritDoc
     */
    public function execute(UnsnoozePicklistInput $input): UnsnoozePicklistResult
    {
        $picklist = $this->picklistRepository->findByReference($input->picklistReference());
        $picklist->unsnooze();
        $this->picklistRepository->update($picklist);

        return new UnsnoozePicklistResult($picklist);
    }
}
