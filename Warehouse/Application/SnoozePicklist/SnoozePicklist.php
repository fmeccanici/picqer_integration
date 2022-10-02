<?php


namespace App\Warehouse\Application\SnoozePicklist;

use App\Warehouse\Domain\Exceptions\PicklistNotFoundException;
use App\Warehouse\Domain\Repositories\PicklistRepositoryInterface;
use Carbon\CarbonImmutable;

class SnoozePicklist implements SnoozePicklistInterface
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
     * @throws PicklistNotFoundException
     */
    public function execute(SnoozePicklistInput $input): SnoozePicklistResult
    {
        $picklist = $this->picklistRepository->findOneById($input->picklistId());

        if (! $picklist)
        {
            throw new PicklistNotFoundException('Picklist with id ' . $input->picklistId() . ' not found');
        }

        $picklist->snoozeUntil(CarbonImmutable::createFromFormat("d-m-Y", $input->snoozeUntil()));
        $this->picklistRepository->update($picklist);

        return new SnoozePicklistResult($picklist);
    }
}
