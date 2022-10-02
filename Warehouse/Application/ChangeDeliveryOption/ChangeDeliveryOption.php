<?php


namespace App\Warehouse\Application\ChangeDeliveryOption;

use App\Warehouse\Domain\Exceptions\ChangeDeliveryOptionException;
use App\Warehouse\Domain\Repositories\OrderRepositoryInterface;
use App\Warehouse\Domain\Repositories\ShipmentRepositoryInterface;

class ChangeDeliveryOption implements ChangeDeliveryOptionInterface
{
    private OrderRepositoryInterface $orderRepository;
    private ShipmentRepositoryInterface $shipmentRepository;

    /**
     * ChangeDeliveryOption constructor.
     * @param OrderRepositoryInterface $orderRepository
     * @param ShipmentRepositoryInterface $shipmentRepository
     */
    public function __construct(OrderRepositoryInterface $orderRepository,
                                ShipmentRepositoryInterface $shipmentRepository)
    {
        $this->orderRepository = $orderRepository;
        $this->shipmentRepository = $shipmentRepository;
    }

    /**
     * @inheritDoc
     * @throws ChangeDeliveryOptionException
     */
    public function execute(ChangeDeliveryOptionInput $input): ChangeDeliveryOptionResult
    {
        $order = $this->orderRepository->findOneByReference($input->orderReference());

        if ($order === null)
        {
            throw new ChangeDeliveryOptionException("Order met referentie " . $input->orderReference() . " niet gevonden");
        }

        $shipments = $this->shipmentRepository->findAllByOrderReference($order->reference());

        if ($shipments->isNotEmpty())
        {
            throw new ChangeDeliveryOptionException("Kan geen bezorgoptie wijzigen, want er is al een zending voor bestelling " . $order->reference());
        }

        $order->changeDeliveryOption($input->country(), $input->deliveryOptionName(), $input->carrierName());

        if ($order->deliveryOption()->isPickupLocationNetherlands() ||
            $order->deliveryOption()->isPickupLocationBelgium())
        {
            if ($input->deliveryAddress() === null)
            {
                throw new ChangeDeliveryOptionException("Bij wijzigen naar een afhaalpunt moet het afleveradres ook gewijzigd worden");
            }

            // TODO: Order heeft een aflever adres Task 18822: Refactor order zodat deze een delivery address heeft en gebruik deze
            $order->customer()->changeDeliveryAddress($input->deliveryAddress());
        }

        $this->orderRepository->update($order, ['delivery_information']);

        return new ChangeDeliveryOptionResult($order);
    }
}
