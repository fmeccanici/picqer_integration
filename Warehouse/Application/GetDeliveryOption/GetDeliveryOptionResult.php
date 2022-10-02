<?php


namespace App\Warehouse\Application\GetDeliveryOption;


use App\Warehouse\Domain\Orders\DeliveryOption;

final class GetDeliveryOptionResult
{
    private ?DeliveryOption $deliveryOption;

    /**
     * GetDeliveryOptionResult constructor.
     * @param ?DeliveryOption $deliveryOption
     */
    public function __construct(?DeliveryOption $deliveryOption)
    {
        $this->deliveryOption = $deliveryOption;
    }

    public function deliveryOption(): ?DeliveryOption
    {
        return $this->deliveryOption;
    }
}
