<?php


namespace App\Warehouse\Application\ChangeOrderDeliveryDate;

use App\Warehouse\Domain\Exceptions\OrderNotFoundException;
use App\Warehouse\Domain\Picklists\Picklist;
use App\Warehouse\Domain\Repositories\OrderRepositoryInterface;
use App\Warehouse\Domain\Repositories\PicklistRepositoryInterface;
use Carbon\CarbonImmutable;

class ChangeOrderDeliveryDate implements ChangeOrderDeliveryDateInterface
{
    protected OrderRepositoryInterface $orderRepository;
    protected PicklistRepositoryInterface $picklistRepository;

    /**
     * ChangeOrderDeliveryDate constructor.
     */
    public function __construct(OrderRepositoryInterface $orderRepository,
                                PicklistRepositoryInterface $picklistRepository)
    {
        $this->orderRepository = $orderRepository;
        $this->picklistRepository = $picklistRepository;
    }

    /**
     * @inheritDoc
     * @throws OrderNotFoundException
     */
    public function execute(ChangeOrderDeliveryDateInput $input): ChangeOrderDeliveryDateResult
    {
        $order = $this->orderRepository->findOneByReference($input->orderReference());

        if ($order === null)
        {
            throw new OrderNotFoundException('Order with reference ' . $input->orderReference() . ' not found');
        }

        $order->changePreferredDeliveryDate(CarbonImmutable::parse($input->deliveryDate()));

        $this->orderRepository->update($order);
        $order->picklists()->each(function (Picklist $picklist) {
            $this->picklistRepository->update($picklist);
        });

        return new ChangeOrderDeliveryDateResult($order);
    }
}
