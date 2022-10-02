<?php


namespace App\Warehouse\Application\HandlePicklistCreated;

use App\Warehouse\Domain\Exceptions\OrderNotFoundException;
use App\Warehouse\Domain\Exceptions\PicklistNotFoundException;
use App\Warehouse\Domain\Picklists\Picklist;
use App\Warehouse\Domain\Repositories\OrderRepositoryInterface;
use App\Warehouse\Domain\Repositories\PicklistRepositoryInterface;
use App\Warehouse\Domain\Services\WarehouseServiceInterface;
use App\Warehouse\Infrastructure\Persistence\Picqer\Orders\Mappers\OrderMapper;
use Carbon\CarbonImmutable;

class HandlePicklistCreated implements HandlePicklistCreatedInterface
{
    private PicklistRepositoryInterface $picklistRepository;
    private WarehouseServiceInterface $warehouseService;
    protected OrderRepositoryInterface $orderRepository;

    /**
     * HandlePicklistCreated constructor.
     * @param PicklistRepositoryInterface $picklistRepository
     * @param WarehouseServiceInterface $warehouseService
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(PicklistRepositoryInterface $picklistRepository,
                                WarehouseServiceInterface $warehouseService,
                                OrderRepositoryInterface $orderRepository)
    {
        $this->picklistRepository = $picklistRepository;
        $this->warehouseService = $warehouseService;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @inheritDoc
     * @throws PicklistNotFoundException
     * @throws OrderNotFoundException
     */
    public function execute(HandlePicklistCreatedInput $input): HandlePicklistCreatedResult
    {
        $picklistId = $input->picklistId();
        $picklist = $this->picklistRepository->findOneById($picklistId);

        if ($picklist === null)
        {
            throw new PicklistNotFoundException('Picklist with id ' . $picklistId . ' not found');
        }

        $order = $this->orderRepository->findOneByReference($picklist->orderReference());

        if ($order === null)
        {
            throw new OrderNotFoundException('Order with reference ' . $picklist->orderReference() . ' not found');
        }

        $order->addTag(Picklist::READY_TO_BE_PROCESSED_TAG);

        if ($order->customer()->isBolCom())
        {
            $order->addTag(OrderMapper::BOL_COM_TAG);
        }

        $this->orderRepository->update($order, ['tags']);

        $picklist->snooze();
        $snoozeUntil = $picklist->snoozedUntil();

        if (CarbonImmutable::now()->startOfDay()->diffInDays($snoozeUntil, false) > 0 && ! $order->customer()->isBolCom())
        {
            $this->warehouseService->snoozePicklistUntil($picklistId, $snoozeUntil);
        } else {
            $picklist->unsnooze();
        }

        if ($order->customer()->isBolCom())
        {
            $picklist->makeUrgent();
            $this->picklistRepository->update($picklist);
        }

        return new HandlePicklistCreatedResult($picklist);
    }
}
