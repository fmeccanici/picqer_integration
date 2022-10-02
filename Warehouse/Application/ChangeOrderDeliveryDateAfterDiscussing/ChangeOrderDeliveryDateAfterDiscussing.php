<?php


namespace App\Warehouse\Application\ChangeOrderDeliveryDateAfterDiscussing;

use App\Warehouse\Domain\Exceptions\OrderNotFoundException;
use App\Warehouse\Domain\Picklists\Picklist;
use App\Warehouse\Domain\Repositories\OrderRepositoryInterface;
use App\Warehouse\Domain\Repositories\PicklistRepositoryInterface;
use Carbon\CarbonImmutable;

class ChangeOrderDeliveryDateAfterDiscussing implements ChangeOrderDeliveryDateAfterDiscussingInterface
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
    public function execute(ChangeOrderDeliveryDateAfterDiscussingInput $input): ChangeOrderDeliveryDateAfterDiscussingResult
    {
        $order = $this->orderRepository->findOneByReference($input->orderReference());

        if ($order === null)
        {
            throw new OrderNotFoundException('Order with reference ' . $input->orderReference() . ' not found');
        }

        $order->changePreferredDeliveryDate(CarbonImmutable::parse($input->deliveryDate()), true);
        $this->orderRepository->update($order, ['delivery_information']);
        $order->picklists()->each(function (Picklist $picklist) {
            $this->picklistRepository->update($picklist);
        });
        return new ChangeOrderDeliveryDateAfterDiscussingResult($order);
    }
}
